<?php

namespace App\Models;

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
    ];

    protected static function booted()
    {
        static::saving(function (SolicitacaoAgendamento $solicitacao) {
            $solicitacao->cliente_documento = static::somenteDigitos($solicitacao->cliente_documento);
            $solicitacao->gru = static::somenteDigitos($solicitacao->gru);

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
     */
    public function editavel(): bool
    {
        return $this->agendamento?->status !== AgendamentoMarinha::STATUS_AGENDADO;
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
