<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Authenticatable
{
    use HasFactory;

    protected $guarded = []; // Ou seus fillables

    protected $fillable = [];

    /**
     * Adicione ESTA função para corrigir o erro:
     */
    public function embarcacoes(): HasMany
    {
        return $this->hasMany(Embarcacao::class);
    }

    public function simulados(): HasMany
    {
        return $this->hasMany(SimuladoResultado::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function simulado_resultados()
    {
        // Este nome DEVE ser igual ao que usamos no Resource
        return $this->hasMany(\App\Models\SimuladoResultado::class, 'cliente_id');
    }
}