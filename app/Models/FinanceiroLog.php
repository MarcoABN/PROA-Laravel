<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceiroLog extends Model
{
    protected $table = 'financeiro_logs';

    protected $guarded = [];

    const EVENTO_CRIADO     = 'criado';
    const EVENTO_ATUALIZADO = 'atualizado';
    const EVENTO_EXCLUIDO   = 'excluido';

    protected $casts = [
        'alteracoes' => 'array',
    ];

    public static function eventos(): array
    {
        return [
            self::EVENTO_CRIADO     => 'Criado',
            self::EVENTO_ATUALIZADO => 'Alterado',
            self::EVENTO_EXCLUIDO   => 'Excluído',
        ];
    }

    /**
     * Rótulo do que foi auditado, por classe de model.
     */
    public static function tiposDeRegistro(): array
    {
        return [
            LancamentoFinanceiro::class => 'Lançamento',
            CategoriaFinanceira::class  => 'Tipo de Entrada / Despesa',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getEventoRotuloAttribute(): string
    {
        return self::eventos()[$this->evento] ?? $this->evento;
    }

    public function getRegistroTipoRotuloAttribute(): string
    {
        return self::tiposDeRegistro()[$this->registro_tipo] ?? class_basename($this->registro_tipo);
    }

    /**
     * O registro auditado, quando ainda existe. Retorna null se foi excluído —
     * o log continua legível pelo retrato em registro_descricao.
     */
    public function registro(): ?Model
    {
        if (! class_exists($this->registro_tipo)) {
            return null;
        }

        return $this->registro_tipo::find($this->registro_id);
    }

    public function scopeDoRegistro(Builder $query, Model $model): Builder
    {
        return $query
            ->where('registro_tipo', $model::class)
            ->where('registro_id', $model->getKey());
    }

    public function scopeNoPeriodo(Builder $query, $inicio, $fim): Builder
    {
        return $query
            ->when($inicio, fn(Builder $q) => $q->whereDate('created_at', '>=', $inicio))
            ->when($fim, fn(Builder $q) => $q->whereDate('created_at', '<=', $fim));
    }
}
