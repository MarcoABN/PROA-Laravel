<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscolaNautica extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function instrutor(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'instrutor_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'responsavel_id');
    }
}