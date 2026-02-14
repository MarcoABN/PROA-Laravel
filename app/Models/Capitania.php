<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capitania extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome', 'sigla', 'uf', 'padrao',
        // --- NOVOS CAMPOS QUE FALTAVAM AQUI ---
        'capitao_nome',
        'capitao_patente',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade'
    ];

    protected $casts = [
        'padrao' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($capitania) {
            if ($capitania->padrao) {
                static::where('id', '!=', $capitania->id)->update(['padrao' => false]);
            }
        });
    }
}