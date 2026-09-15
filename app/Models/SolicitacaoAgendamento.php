<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Um serviço de um cliente a ser agendado pelo procurador: CPF + GRU + serviço.
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
            $solicitacao->cliente_cpf = static::somenteDigitos($solicitacao->cliente_cpf);
            $solicitacao->gru = static::somenteDigitos($solicitacao->gru);

            // Se o CPF já é cliente do PROA, vincula e aproveita o nome.
            if ($solicitacao->isDirty('cliente_cpf') || !$solicitacao->cliente_id) {
                $cliente = Cliente::where('cpfcnpj', $solicitacao->cliente_cpf)->first();
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

    public static function formatarCpf(?string $cpf): string
    {
        $digitos = static::somenteDigitos($cpf);

        return strlen($digitos) === 11
            ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digitos)
            : (string) $cpf;
    }
}
