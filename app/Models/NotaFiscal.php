<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaFiscal extends Model
{
    protected $guarded = [];
    
    public function embarcacao(): BelongsTo
    {
        return $this->belongsTo(Embarcacao::class);
    }
}
