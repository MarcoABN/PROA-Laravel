<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Embarcacao extends Model
{
    // Liberar mass assignment (Segurança)
    protected $guarded = [];

    // Define o nome da tabela no plural correto (opcional, mas bom garantir)
    protected $table = 'embarcacoes';

    // Pertence a um Cliente
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    // Tem muitos Motores
    public function motores(): HasMany
    {
        return $this->hasMany(Motor::class);
    }

    // Tem uma Nota Fiscal
    public function notaFiscal(): HasOne
    {
        return $this->hasOne(NotaFiscal::class);
    }

    protected function casts(): array
    {
        return [
            'potencia_motor' => 'integer',
        ];
    }
}