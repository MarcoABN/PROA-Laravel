<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Feriados nacionais (fixos e os que dependem da Páscoa). Carnaval e Corpus Christi são ponto
 * facultativo federal, mas entram porque as capitanias normalmente não atendem nesses dias.
 */
class Feriados
{
    private const FIXOS = [
        '01-01' => 'Confraternização Universal',
        '04-21' => 'Tiradentes',
        '05-01' => 'Dia do Trabalho',
        '09-07' => 'Independência do Brasil',
        '10-12' => 'Nossa Senhora Aparecida',
        '11-02' => 'Finados',
        '11-15' => 'Proclamação da República',
        '11-20' => 'Dia da Consciência Negra',
        '12-25' => 'Natal',
    ];

    /** @return array<string, string> 'AAAA-MM-DD' => nome, em ordem de data */
    public static function doAno(int $ano): array
    {
        $feriados = [];

        foreach (self::FIXOS as $mesDia => $nome) {
            $feriados["{$ano}-{$mesDia}"] = $nome;
        }

        $pascoa = static::pascoa($ano);
        $feriados[$pascoa->copy()->subDays(48)->toDateString()] = 'Carnaval';
        $feriados[$pascoa->copy()->subDays(47)->toDateString()] = 'Carnaval';
        $feriados[$pascoa->copy()->subDays(2)->toDateString()] = 'Sexta-feira Santa';
        $feriados[$pascoa->copy()->addDays(60)->toDateString()] = 'Corpus Christi';

        ksort($feriados);

        return $feriados;
    }

    /** @return array<string, string> feriados de todos os anos do intervalo */
    public static function dosAnos(int $de, int $ate): array
    {
        $feriados = [];

        for ($ano = $de; $ano <= $ate; $ano++) {
            $feriados += static::doAno($ano);
        }

        return $feriados;
    }

    /** Domingo de Páscoa (algoritmo de Meeus/Jones/Butcher), sem depender da extensão calendar do PHP. */
    public static function pascoa(int $ano): Carbon
    {
        $a = $ano % 19;
        $b = intdiv($ano, 100);
        $c = $ano % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($ano, $mes, $dia)->startOfDay();
    }
}
