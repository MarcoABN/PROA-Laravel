<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OficioInstrutor extends Model
{
    protected $table = 'oficio_instrutores';
    
    protected $fillable = ['oficio_id', 'prestador_id', 'is_principal'];

    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'prestador_id');
    }
}