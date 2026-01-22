<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capitania extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'sigla', 'uf', 'padrao'];

    protected $casts = [
        'padrao' => 'boolean',
    ];

    // Lógica para garantir apenas uma Capitania Padrão
    protected static function booted()
    {
        static::saving(function ($capitania) {
            if ($capitania->padrao) {
                // Desmarca todas as outras antes de salvar esta como padrão
                static::where('id', '!=', $capitania->id)->update(['padrao' => false]);
            }
        });
    }
}