<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Porteiro do menu "Gestão Financeira".
 *
 * Centraliza a regra num lugar só para que as telas do grupo (lançamentos,
 * categorias e consolidado) não saiam do ar por engano quando a regra mudar.
 */
class AcessoFinanceiro
{
    public static function permitido(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->pode_acessar_financeiro;
    }
}
