<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tradução entre a sigla usada nas listas (INSC EMB, RNV EMB...) e o texto exato
 * da opção que a extensão precisa escolher no SISAP.
 */
class SisapServico extends Model
{
    protected $table = 'sisap_servicos';

    protected $guarded = [];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    protected $attributes = [
        'ativo' => true,
    ];

    public function solicitacoes(): HasMany
    {
        return $this->hasMany(SolicitacaoAgendamento::class, 'sisap_servico_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
