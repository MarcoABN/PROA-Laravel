<?php

namespace App\Models\Concerns;

use App\Models\SisapToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * Tokens da extensão do Chrome (tabela sisap_tokens): um por navegador, revogáveis um a um.
 * O valor devolvido na geração precisa ser copiado na hora; só o hash fica no banco.
 */
trait TemTokenSisap
{
    public function tokensSisap(): MorphMany
    {
        return $this->morphMany(SisapToken::class, 'dono')->latest();
    }

    /** Gera mais um token. Os que já existem continuam valendo. */
    public function gerarTokenSisap(string $nome): string
    {
        $token = Str::random(48);

        $this->tokensSisap()->create([
            'nome' => trim($nome),
            'hash' => SisapToken::hash($token),
        ]);

        return $token;
    }

    /** @param  array<int, int>  $ids */
    public function revogarTokensSisap(array $ids): int
    {
        return $this->tokensSisap()->whereKey($ids)->delete();
    }

    public function scopeComTokenSisap(Builder $query, string $token): Builder
    {
        return $query->whereHas('tokensSisap', fn(Builder $q) => $q->where('hash', SisapToken::hash($token)));
    }
}
