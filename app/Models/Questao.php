<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Questao extends Model
{
    protected $table = 'questoes'; // Força o nome correto da tabela
    protected $fillable = [
        'categoria',
        'assunto',
        'enunciado',
        'alternativa_a',
        'alternativa_b',
        'alternativa_c',
        'alternativa_d',
        'alternativa_e',
        'resposta_correta',
        'ativo'
    ];
}