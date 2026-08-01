<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

/**
 * Preferência de posição do menu do painel (lateral ou superior).
 *
 * Guardada em cookie, não no banco: é uma preferência de layout que depende
 * do tamanho da tela em uso, então faz sentido variar por dispositivo — e
 * assim não exige alteração de schema.
 */
class PreferenciaNavegacao
{
    public const COOKIE = 'proa_posicao_menu';

    public const LATERAL = 'lateral';
    public const SUPERIOR = 'superior';

    /** Um ano, em minutos. */
    public const DURACAO = 525600;

    public static function posicoesValidas(): array
    {
        return [self::LATERAL, self::SUPERIOR];
    }

    public static function posicao(): string
    {
        $valor = Cookie::get(self::COOKIE);

        return in_array($valor, self::posicoesValidas(), true)
            ? $valor
            : self::LATERAL; // padrão: menu lateral, como o sistema sempre funcionou
    }

    public static function ehSuperior(): bool
    {
        return self::posicao() === self::SUPERIOR;
    }
}
