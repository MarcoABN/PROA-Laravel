<?php

namespace App\Support;

use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;

/**
 * Endereço público do PROA, como o usuário o acessa no navegador.
 *
 * Em produção o PROA roda atrás de nginx (e possivelmente num subcaminho), então APP_URL e o host
 * da requisição nem sempre batem com o que o usuário digita. Dentro do painel, o Referer da
 * requisição do Livewire é a própria página aberta (ex.: https://servidor/proa/admin/agendamentos-marinha);
 * tirando a parte do painel sobra o endereço que a extensão precisa.
 */
class EnderecoPublico
{
    public static function proa(): string
    {
        $caminhoPainel = trim(Filament::getCurrentPanel()?->getPath() ?? 'admin', '/');
        $referer = (string) request()->headers->get('referer');

        if ($referer !== '' && preg_match('#^(https?://[^?\#]*?)/' . preg_quote($caminhoPainel, '#') . '(?:[/?\#]|$)#i', $referer, $m)) {
            return rtrim($m[1], '/');
        }

        return rtrim(url('/'), '/');
    }

    /** Texto da notificação do token: os dois valores que vão nas opções da extensão. */
    public static function instrucoesExtensao(string $token): HtmlString
    {
        return new HtmlString(
            'Cole nas opções da extensão. O token não será exibido de novo.<br><br>'
            . '<strong>Endereço do PROA:</strong><br><code style="user-select: all; word-break: break-all;">' . e(static::proa()) . '</code><br><br>'
            . '<strong>Token:</strong><br><code style="user-select: all; word-break: break-all;">' . e($token) . '</code>'
        );
    }
}
