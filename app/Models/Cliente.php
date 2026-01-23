<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany; // <--- Importante

class Cliente extends Authenticatable
{
    use HasFactory;

    protected $guarded = []; // Ou seus fillables

    protected $fillable = [
        'nome',
        'cpfcnpj',
        'email',
    ];

    /**
     * Adicione ESTA função para corrigir o erro:
     */
    public function embarcacoes(): HasMany
    {
        return $this->hasMany(Embarcacao::class);
    }
}