<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Porteiro da tela "Usuários do Sistema".
 *
 * Espelha o AcessoFinanceiro, mas com dois níveis: abrir a tela é uma coisa,
 * conceder permissões é outra.
 */
class AcessoUsuarios
{
    public static function permitido(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->podeGerenciarUsuarios();
    }

    public static function podeConcederPermissoes(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->podeConcederPermissoes();
    }
}
