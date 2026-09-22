<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Artigo extends Model
{
    protected $fillable = [
        'titulo', 'slug', 'resumo', 'conteudo',
        'titulo_seo', 'meta_descricao', 'faq', 'ativo', 'publicado_em',
    ];

    protected $attributes = [
        'ativo' => true,
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'faq' => 'array',
        'publicado_em' => 'datetime',
    ];

    // Ativos e com data de publicação já alcançada (permite agendar artigos)
    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('ativo', true)
            ->whereNotNull('publicado_em')
            ->where('publicado_em', '<=', now());
    }

    public function estaPublicado(): bool
    {
        return $this->ativo && $this->publicado_em && $this->publicado_em->lte(now());
    }
}
