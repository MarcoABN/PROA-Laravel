<?php

namespace App\Models;

use App\Models\Concerns\TemTokenSisap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestador extends Model
{
    use HasFactory, TemTokenSisap;

    // AVISA O LARAVEL QUE O NOME DA TABELA É EM PORTUGUÊS
    protected $table = 'prestadores';

    protected $guarded = [];

    protected $hidden = ['sisap_token_hash'];

    protected $casts = [
        'dt_emissao' => 'date',
        'cha_dtemissao' => 'date',
        'sisap_token_gerado_em' => 'datetime',
        'sisap_extensao_vista_em' => 'datetime',
        // ... outros campos
    ];

    public function agendamentosMarinha(): HasMany
    {
        return $this->hasMany(AgendamentoMarinha::class);
    }

    /** Token individual da extensão: só atende este procurador. */
    public static function porTokenSisap(string $token): ?self
    {
        return static::where('is_procurador', true)
            ->where('sisap_token_hash', static::hashTokenSisap($token))
            ->first();
    }

    /** Procurador cadastrado com este CPF (cpfcnpj é gravado só com dígitos). */
    public static function procuradorPorCpf(?string $cpf): ?self
    {
        $digitos = preg_replace('/\D/', '', (string) $cpf);

        return strlen($digitos) === 11
            ? static::where('is_procurador', true)->where('cpfcnpj', $digitos)->first()
            : null;
    }
}