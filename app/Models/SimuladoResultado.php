<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimuladoResultado extends Model
{
    protected $table = 'simulado_resultados';
    
    protected $fillable = [
        'cliente_id', // Alterado
        'modalidade', 
        'acertos', 
        'erros',      // Novo
        'total', 
        'porcentagem', 
        'aprovado'
    ];

    // Relacionamento com Cliente (Não mais com User)
    public function cliente() {
        return $this->belongsTo(Cliente::class);
    }
}