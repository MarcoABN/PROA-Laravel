<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // <--- Importante

class Cliente extends Model
{
    use HasFactory;

    protected $guarded = []; // Ou seus fillables

    // ... (outros códigos que já existam) ...

    /**
     * Adicione ESTA função para corrigir o erro:
     */
    public function embarcacoes(): HasMany
    {
        return $this->hasMany(Embarcacao::class);
    }
}