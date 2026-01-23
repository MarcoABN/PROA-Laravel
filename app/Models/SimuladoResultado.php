<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimuladoResultado extends Model
{
    protected $table = 'simulado_resultados';
    protected $fillable = ['user_id', 'modalidade', 'acertos', 'total', 'porcentagem', 'aprovado'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}