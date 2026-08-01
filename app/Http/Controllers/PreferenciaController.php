<?php

namespace App\Http\Controllers;

use App\Support\PreferenciaNavegacao;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class PreferenciaController extends Controller
{
    /**
     * Alterna a posição do menu do painel entre lateral e superior.
     * Volta para a página de origem para o usuário não perder o contexto.
     */
    public function navegacao(Request $request, string $posicao)
    {
        abort_unless(
            in_array($posicao, PreferenciaNavegacao::posicoesValidas(), true),
            404
        );

        return redirect()
            // Volta para a página de origem; sem referer, cai no painel.
            ->back(fallback: Filament::getUrl())
            ->withCookie(cookie(
                PreferenciaNavegacao::COOKIE,
                $posicao,
                PreferenciaNavegacao::DURACAO
            ));
    }
}
