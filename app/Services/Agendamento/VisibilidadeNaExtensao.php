<?php

namespace App\Services\Agendamento;

use App\Models\MesAgendamento;
use App\Support\Feriados;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Quais meses de atendimento aparecem na extensão.
 *
 * - O mês seguinte é o PRINCIPAL e aparece sempre: a agenda dele abre no último dia útil do mês
 *   corrente e as vagas acabam em minutos.
 * - O mês corrente continua aparecendo na primeira semana (dias 1 a 7): o principal que falhou na
 *   abertura costuma ser feito no dia útil seguinte.
 * - Fora isso, um mês só aparece com "Exibir na extensão" ligado (tela do mês, no PROA).
 */
class VisibilidadeNaExtensao
{
    public const DIAS_DA_PRIMEIRA_SEMANA = 7;

    public const PRINCIPAL = 'principal';
    public const PRIMEIRA_SEMANA = 'primeira_semana';
    public const LIBERADO = 'liberado';

    /** Por que o mês aparece na extensão (uma das constantes) ou null se não aparece. */
    public static function motivo(mixed $competencia, ?CarbonInterface $hoje = null): ?string
    {
        $hoje = Carbon::instance($hoje ?? now());
        $mes = Carbon::parse($competencia)->startOfMonth();
        $corrente = $hoje->copy()->startOfMonth();

        if ($mes->equalTo($corrente->copy()->addMonth())) {
            return self::PRINCIPAL;
        }

        if ($mes->equalTo($corrente) && $hoje->day <= self::DIAS_DA_PRIMEIRA_SEMANA) {
            return self::PRIMEIRA_SEMANA;
        }

        // Mês que já passou não tem mais agenda para marcar.
        if ($mes->gte($corrente) && MesAgendamento::exibirNaExtensao($mes)) {
            return self::LIBERADO;
        }

        return null;
    }

    /** @return array<int, string> competências (Y-m-01) que aparecem na extensão */
    public static function competencias(?CarbonInterface $hoje = null): array
    {
        $hoje = Carbon::instance($hoje ?? now());
        $corrente = $hoje->copy()->startOfMonth();

        $candidatas = MesAgendamento::where('exibir_na_extensao', true)
            ->whereDate('competencia', '>=', $corrente->toDateString())
            ->pluck('competencia')
            ->map(fn($data) => Carbon::parse($data)->toDateString())
            ->push($corrente->toDateString(), $corrente->copy()->addMonth()->toDateString())
            ->unique();

        return $candidatas->filter(fn(string $mes) => static::motivo($mes, $hoje) !== null)->values()->all();
    }

    /** Dia em que a agenda do mês abre no SISAP: o último dia útil do mês anterior. */
    public static function abertura(mixed $competencia): Carbon
    {
        $dia = Carbon::parse($competencia)->startOfMonth()->subDay();
        $feriados = Feriados::doAno($dia->year);

        while ($dia->isWeekend() || isset($feriados[$dia->toDateString()])) {
            $dia->subDay();
        }

        return $dia;
    }

    public static function rotulo(?string $motivo): ?string
    {
        return match ($motivo) {
            self::PRINCIPAL => 'Sempre na extensão: é o mês principal (agenda abre no último dia útil do mês anterior).',
            self::PRIMEIRA_SEMANA => 'Na extensão até o dia ' . self::DIAS_DA_PRIMEIRA_SEMANA . ' (primeira semana do mês). Depois, só se "Exibir na extensão" estiver ligado.',
            self::LIBERADO => 'Na extensão: "Exibir na extensão" ligado.',
            default => null,
        };
    }
}
