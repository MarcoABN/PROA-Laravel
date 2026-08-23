<?php

namespace App\Models;

use App\Models\Concerns\RegistraLogFinanceiro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaFinanceira extends Model
{
    use RegistraLogFinanceiro;

    protected $table = 'categorias_financeiras';

    protected $guarded = [];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    protected $attributes = [
        'ativo' => true,
    ];

    public function lancamentos(): HasMany
    {
        return $this->hasMany(LancamentoFinanceiro::class, 'categoria_financeira_id');
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    // --- Auditoria (RegistraLogFinanceiro) ---

    public function rotulosAuditoria(): array
    {
        return [
            'nome'      => 'Nome',
            'tipo'      => 'Aplica-se a',
            'descricao' => 'Descrição',
            'ativo'     => 'Ativo',
        ];
    }

    public function descricaoAuditoria(): string
    {
        $tipo = LancamentoFinanceiro::tipos()[$this->tipo] ?? $this->tipo;

        return "{$this->nome} ({$tipo})";
    }

    protected function formatarValorAuditoria(string $campo, $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return match ($campo) {
            'tipo'  => LancamentoFinanceiro::tipos()[$valor] ?? (string) $valor,
            'ativo' => $valor ? 'Sim' : 'Não',
            default => (string) $valor,
        };
    }
}
