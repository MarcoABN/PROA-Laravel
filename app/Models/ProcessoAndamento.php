<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoAndamento extends Model
{
    protected $guarded = [];

    public function processo(): BelongsTo { return $this->belongsTo(Processo::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}