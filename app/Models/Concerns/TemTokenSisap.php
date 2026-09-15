<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Token da extensão do Chrome (colunas sisap_token_hash e sisap_token_gerado_em).
 * Só o hash fica no banco: o valor devolvido na geração precisa ser copiado na hora.
 */
trait TemTokenSisap
{
    /** Gera um novo token, invalidando o anterior. */
    public function gerarTokenSisap(): string
    {
        $token = Str::random(48);

        $this->forceFill([
            'sisap_token_hash' => static::hashTokenSisap($token),
            'sisap_token_gerado_em' => now(),
        ])->save();

        return $token;
    }

    protected static function hashTokenSisap(string $token): string
    {
        return hash('sha256', $token);
    }
}
