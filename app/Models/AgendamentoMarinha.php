<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Um agendamento do procurador no SISAP: agrupa até N solicitações (vagas) numa data/hora.
 * Não tem tela própria — é criado e descartado por AlocaSolicitacao.
 */
class AgendamentoMarinha extends Model
{
    protected $table = 'agendamentos_marinha';

    protected $guarded = [];

    // pendente → agendado | falhou (a extensão tenta de novo); cancelado libera a cota.
    const STATUS_PENDENTE  = 'pendente';
    const STATUS_AGENDADO  = 'agendado';
    const STATUS_FALHOU    = 'falhou';
    const STATUS_CANCELADO = 'cancelado';

    const PERIODO_MANHA = 'manha';
    const PERIODO_TARDE = 'tarde';

    protected $casts = [
        'competencia' => 'date',
        'data_hora' => 'datetime',
        'data_sugerida' => 'date',
    ];

    public static function periodos(): array
    {
        return [
            self::PERIODO_MANHA => 'Matutino',
            self::PERIODO_TARDE => 'Vespertino',
        ];
    }

    public function rotuloPreferencia(): ?string
    {
        $partes = array_filter([
            $this->data_sugerida?->format('d/m'),
            static::periodos()[$this->periodo] ?? null,
        ]);

        return $partes ? 'Preferência: ' . implode(' · ', $partes) : null;
    }


    public static function statuses(): array
    {
        return [
            self::STATUS_PENDENTE  => 'Aguardando agendamento',
            self::STATUS_AGENDADO  => 'Agendado',
            self::STATUS_FALHOU    => 'Falhou',
            self::STATUS_CANCELADO => 'Cancelado',
        ];
    }

    public static function coresStatus(): array
    {
        return [
            self::STATUS_PENDENTE  => 'info',
            self::STATUS_AGENDADO  => 'success',
            self::STATUS_FALHOU    => 'danger',
            self::STATUS_CANCELADO => 'warning',
        ];
    }

    /**
     * Mês corrente e os dois seguintes, no formato usado pela coluna competencia (dia 1º).
     */
    public static function competenciasDisponiveis(): array
    {
        $inicio = now()->startOfMonth();

        return collect(range(0, 2))
            ->mapWithKeys(fn(int $i) => [
                $inicio->copy()->addMonths($i)->toDateString() => $inicio->copy()->addMonths($i)->format('m/Y'),
            ])
            ->all();
    }

    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class);
    }

    public function capitania(): BelongsTo
    {
        return $this->belongsTo(Capitania::class);
    }

    public function solicitacoes(): HasMany
    {
        return $this->hasMany(SolicitacaoAgendamento::class, 'agendamento_marinha_id');
    }

    public function vagasTotais(): int
    {
        return (int) ($this->capitania?->sisap_vagas_por_agendamento ?? 3);
    }

    /**
     * Horário que este agendamento deve tentar acompanhar (mesma data, horário vizinho).
     *
     * Prioridade: um cliente deste agendamento que já foi marcado em outro agendamento;
     * senão, o outro agendamento já marcado do mesmo procurador.
     * Ambos restritos à mesma capitania e competência.
     */
    public function referenciaDeHorario(): ?Carbon
    {
        $base = static::query()
            ->whereKeyNot($this->getKey())
            ->where('status', self::STATUS_AGENDADO)
            ->whereNotNull('data_hora')
            ->where('capitania_id', $this->capitania_id)
            ->whereDate('competencia', $this->competencia->toDateString())
            ->orderBy('data_hora');

        $cpfs = $this->solicitacoes()->pluck('cliente_cpf')->unique()->values();

        $porCliente = $cpfs->isEmpty() ? null : (clone $base)
            ->whereHas('solicitacoes', fn($q) => $q->whereIn('cliente_cpf', $cpfs))
            ->value('data_hora');

        $referencia = $porCliente ?? (clone $base)->where('prestador_id', $this->prestador_id)->value('data_hora');

        return $referencia ? Carbon::parse($referencia) : null;
    }
}
