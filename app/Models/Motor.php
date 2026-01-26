<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motor extends Model
{
    protected $guarded = [];
    protected $table = 'motores'; // Laravel pode tentar "motors"

    public function embarcacao(): BelongsTo
    {
        return $this->belongsTo(Embarcacao::class);
    }

    protected function casts(): array
    {
        return [
            'potencia' => 'integer',
        ];
    }
}
