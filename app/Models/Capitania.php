<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capitania extends Model
{
    use HasFactory;

    // ADICIONE OS CAMPOS NOVOS AQUI
    protected $fillable = [
        'nome', 'sigla', 'uf', 'padrao',
        'capitao_nome',    // <--- Faltava
        'capitao_patente', // <--- Faltava
        'cep',             // <--- Faltava
        'logradouro',      // <--- Faltava
        'numero',          // <--- Faltava
        'complemento',     // <--- Faltava
        'bairro',          // <--- Faltava
        'cidade'           // <--- Faltava
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