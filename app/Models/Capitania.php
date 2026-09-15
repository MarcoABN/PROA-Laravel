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
        'cidade',          // <--- Faltava
        // Agendamento eletrônico (SISAP)
        'sisap_nidom',
        'sisap_vagas_por_agendamento',
        'sisap_agendamentos_por_mes',
    ];

    protected $casts = [
        'padrao' => 'boolean',
        'sisap_nidom' => 'integer',
        'sisap_vagas_por_agendamento' => 'integer',
        'sisap_agendamentos_por_mes' => 'integer',
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