<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    protected $fillable = [
        'nome', 'slug', 'descricao', 'icone', 'ativo',
        'titulo_seo', 'meta_descricao', 'conteudo', 'faq',
    ];

    // Garante que o valor padrão seja aplicado no nível do código
    protected $attributes = [
        'ativo' => true,
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'faq' => 'array',
    ];
}
