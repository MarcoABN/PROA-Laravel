<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Um serviço de um cliente a ser agendado pelo procurador: CPF ou CNPJ + GRU + serviço.
 * Cada GRU ocupa uma vaga. Grave sempre pelo cadastro da Capitania + Mês
 * (App\Services\Agendamento\CadastroDoMes), que mantém procurador, capitania e mês coerentes com o agendamento.
 */
class SolicitacaoAgendamento extends Model
{
    protected $table = 'agendamento_solicitacoes';

    protected $guarded = [];

    protected $casts = [
        'competencia' => 'date',
        'descartada_em' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function (SolicitacaoAgendamento $solicitacao) {
            $solicitacao->cliente_documento = static::somenteDigitos($solicitacao->cliente_documento);
            $solicitacao->gru = static::somenteDigitos($solicitacao->gru);

            // Corrigiu o que o SISAP recusou: o cliente volta a ser enviado.
            if ($solicitacao->exists && $solicitacao->isDirty(['cliente_documento', 'gru', 'sisap_servico_id']) && !$solicitacao->isDirty('descartada_em')) {
                $solicitacao->descartada_em = null;
                $solicitacao->motivo_descarte = null;
            }

            // Se o CPF/CNPJ já é cliente do PROA, vincula e aproveita o nome.
            if ($solicitacao->isDirty('cliente_documento') || !$solicitacao->cliente_id) {
                $cliente = Cliente::where('cpfcnpj', $solicitacao->cliente_documento)->first();
                $solicitacao->cliente_id = $cliente?->id;
                $solicitacao->cliente_nome = $solicitacao->cliente_nome ?: $cliente?->nome;
            }
        });

        // Capitania + mês é a chave do cadastro: uma vez gravada, não muda.
        static::updating(function (SolicitacaoAgendamento $solicitacao) {
            if ($solicitacao->isDirty(['capitania_id', 'competencia'])) {
                throw new LogicException('A capitania e o mês de um cliente do agendamento não podem ser alterados.');
            }
        });

        static::creating(function (SolicitacaoAgendamento $solicitacao) {
            $solicitacao->user_id ??= Auth::id();
        });
    }

    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class);
    }

    public function capitania(): BelongsTo
    {
        return $this->belongsTo(Capitania::class);
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(AgendamentoMarinha::class, 'agendamento_marinha_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(SisapServico::class, 'sisap_servico_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enquanto o agendamento não foi marcado no SISAP, o serviço ainda pode ser alterado ou removido.
     * O descartado nunca foi para o SISAP, então pode ser excluído mesmo depois.
     */
    public function editavel(): bool
    {
        return $this->descartada() || $this->agendamento?->status !== AgendamentoMarinha::STATUS_AGENDADO;
    }

    public function descartada(): bool
    {
        return $this->descartada_em !== null;
    }

    public function descartar(string $motivo): void
    {
        $this->forceFill(['descartada_em' => now(), 'motivo_descarte' => $motivo])->save();
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->whereNull('descartada_em');
    }

    /**
     * Onde cada GRU já foi usada em OUTROS meses (no mesmo mês ela é única). Pode ser reaproveitada,
     * mas o usuário precisa ver: "10/2026 · CFGO · Agendado".
     *
     * @param  iterable<string>  $grus
     * @return array<string, list<string>>  gru => usos
     */
    public static function usosEmOutrosMeses(iterable $grus, mixed $competencia): array
    {
        $grus = collect($grus)->map(fn($gru) => static::somenteDigitos($gru))->filter()->unique()->values();

        if ($grus->isEmpty()) {
            return [];
        }

        return static::with(['capitania', 'agendamento'])
            ->whereIn('gru', $grus)
            ->whereDate('competencia', '!=', \Carbon\Carbon::parse($competencia)->startOfMonth()->toDateString())
            ->orderBy('competencia')
            ->get()
            ->groupBy('gru')
            ->map(fn($usos) => $usos->map(fn(SolicitacaoAgendamento $s) => $s->competencia->format('m/Y')
                . ($s->capitania?->sigla ? " · {$s->capitania->sigla}" : '')
                . ' · ' . ($s->descartada() ? 'Descartado' : (AgendamentoMarinha::statuses()[$s->agendamento?->status] ?? 'sem agendamento')))
                ->values()->all())
            ->all();
    }

    /**
     * Clientes em mais de um agendamento da capitania no mês (permitido: serviços em dias diferentes, mas
     * sinalizado). Descartados e agendamentos cancelados não contam.
     *
     * @return array<string, array{nome: string, agendamentos: array<int, string>}>  documento => nome e
     *                                                                                  agendamento_id => "1º de FULANO"
     */
    public static function clientesEmVariosAgendamentos(int $capitaniaId, mixed $competencia): array
    {
        return static::with(['agendamento.prestador'])
            ->whereNull('descartada_em')
            ->where('capitania_id', $capitaniaId)
            ->whereDate('competencia', \Carbon\Carbon::parse($competencia)->startOfMonth()->toDateString())
            ->whereHas('agendamento', fn(Builder $a) => $a->where('status', '!=', AgendamentoMarinha::STATUS_CANCELADO))
            ->get()
            ->groupBy('cliente_documento')
            ->map(fn($servicos) => $servicos->unique('agendamento_marinha_id')->sortBy(fn(SolicitacaoAgendamento $s) => [$s->agendamento->ordem, $s->prestador_id]))
            ->filter(fn($porAgendamento) => $porAgendamento->count() > 1)
            ->map(fn($porAgendamento) => [
                'nome' => $porAgendamento->pluck('cliente_nome')->filter()->first() ?? 'Cliente',
                'agendamentos' => $porAgendamento->mapWithKeys(fn(SolicitacaoAgendamento $s) => [
                    $s->agendamento_marinha_id => "{$s->agendamento->ordem}º de " . ($s->agendamento->prestador?->nome ?? 'procurador')
                        . ($s->agendamento->data_hora ? ' (' . $s->agendamento->data_hora->format('d/m H:i') . ')' : ''),
                ])->all(),
            ])
            ->all();
    }

    public static function somenteDigitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    /** "CPF" (11 dígitos) ou "CNPJ" (14 dígitos), como no "Tipo doc" do SISAP; null se não for nenhum dos dois. */
    public static function tipoDocumento(?string $documento): ?string
    {
        return match (strlen(static::somenteDigitos($documento))) {
            11 => 'CPF',
            14 => 'CNPJ',
            default => null,
        };
    }

    public static function documentoValido(?string $documento): bool
    {
        return static::cpfValido($documento) || static::cnpjValido($documento);
    }

    public static function cpfValido(?string $cpf): bool
    {
        $cpf = static::somenteDigitos($cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            if ((int) $cpf[$posicao] !== ((10 * $soma) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    public static function cnpjValido(?string $cnpj): bool
    {
        $cnpj = static::somenteDigitos($cnpj);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([12, 13] as $posicao) {
            $soma = 0;
            $peso = $posicao - 7;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cnpj[$i] * $peso;
                $peso = $peso === 2 ? 9 : $peso - 1;
            }

            $resto = $soma % 11;
            if ((int) $cnpj[$posicao] !== ($resto < 2 ? 0 : 11 - $resto)) {
                return false;
            }
        }

        return true;
    }

    /** Máscara de CPF ou CNPJ conforme a quantidade de dígitos; outros valores voltam como estão. */
    public static function formatarDocumento(?string $documento): string
    {
        $digitos = static::somenteDigitos($documento);

        return match (strlen($digitos)) {
            11 => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digitos),
            14 => preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digitos),
            default => (string) $documento,
        };
    }
}
