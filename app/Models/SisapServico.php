<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tradução entre o nome usado nas listas (coluna sigla: "Embarcação · Inscrição"...) e o texto
 * da opção que a extensão precisa escolher no SISAP.
 */
class SisapServico extends Model
{
    protected $table = 'sisap_servicos';

    protected $guarded = [];

    protected static function booted()
    {
        // Texto colado de documentos traz travessões e espaços sobrando; a extensão compara com hífen simples.
        static::saving(function (SisapServico $servico) {
            $servico->sigla = trim((string) $servico->sigla);
            $servico->descricao_sisap = preg_replace('/\s+/u', ' ', trim(preg_replace('/[\x{2010}-\x{2015}\x{2212}]/u', '-', (string) $servico->descricao_sisap)));
        });
    }

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
