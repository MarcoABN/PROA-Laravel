<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OficioCliente extends Model
{
    // Permite gravação em massa
    protected $fillable = ['oficio_id', 'cliente_id', 'categoria'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}