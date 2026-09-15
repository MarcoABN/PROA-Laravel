<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Porteiro do Agendamento Marinha.
 *
 * Vale para as telas de agendamento, Serviços do SISAP, o token da extensão (Procuradores)
 * e os dados SISAP das Capitanias. A API da extensão não passa por aqui: ela usa o token do procurador.
 */
class AcessoAgendamento
{
    public static function permitido(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->podeAcessarAgendamento();
    }
}
