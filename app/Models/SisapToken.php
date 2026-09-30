<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Token da extensão do Chrome. Cada navegador (perfil do Chrome) usa o seu; o dono é um usuário do
 * PROA (atende qualquer procurador) ou um procurador (token individual). Só o hash fica no banco.
 */
class SisapToken extends Model
{
    protected $table = 'sisap_tokens';

    protected $guarded = [];

    protected $hidden = ['hash'];

    protected $casts = [
        'usado_em' => 'datetime',
    ];

    public function dono(): MorphTo
    {
        return $this->morphTo();
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function porValor(string $token): ?self
    {
        return static::where('hash', static::hash($token))->first();
    }

    /** Versão da extensão e último uso deste navegador. Grava no máximo uma vez por hora se nada mudou. */
    public function registrarUso(?string $versao): void
    {
        $versao = substr(preg_replace('/[^0-9.]/', '', (string) $versao), 0, 20) ?: null;

        if ($this->extensao_versao === $versao && $this->usado_em?->gt(now()->subHour())) {
            return;
        }

        $this->forceFill(['extensao_versao' => $versao ?? $this->extensao_versao, 'usado_em' => now()])->saveQuietly();
    }

    /** "Chrome escritório · criado em 30/09/2026 · usado em 30/09/2026 10:15 · v0.2.4" */
    public function descricao(): string
    {
        return implode(' · ', array_filter([
            $this->nome,
            'criado em ' . $this->created_at?->format('d/m/Y'),
            $this->usado_em ? 'usado em ' . $this->usado_em->format('d/m/Y H:i') : 'nunca usado',
            $this->extensao_versao ? "v{$this->extensao_versao}" : null,
        ]));
    }
}
