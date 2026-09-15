<?php

namespace App\Services\Agendamento;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Escolhe qual horário livre do SISAP a extensão deve marcar.
 *
 * 1. Com referência (já existe agendamento marcado do mesmo procurador ou do mesmo cliente na
 *    capitania/mês): mesma data, mesmo período e horário mais próximo da referência. Aqui um horário
 *    único basta, porque o primeiro já está garantido.
 *
 * 2. Sem referência: a data escolhida precisa comportar um segundo agendamento no mesmo período, mesmo
 *    que por enquanto o procurador tenha um só. Por isso escolhe o melhor PAR de horários e devolve um
 *    deles, deixando o outro livre. Ordem de preferência:
 *      a) mesma data e ambos no período escolhido — data sugerida primeiro, depois a mais próxima
 *         (anterior ou posterior); dentro da data, horários vizinhos (até 60 min) antes de distantes;
 *      b) mesma data com períodos diferentes, na data mais próxima da sugerida;
 *      c) datas diferentes com ambos no período;
 *      d) qualquer outra combinação.
 *    Uma data com só um horário no período é deixada de lado enquanto houver outra data que comporte os dois.
 *    Se só existir um horário livre, fica com ele.
 *
 * Sem data sugerida, "mais próxima" é a mais cedo disponível. Sem período, vale qualquer período,
 * mas os dois agendamentos continuam preferindo ficar no mesmo.
 */
class EscolhaDeHorario
{
    public const MANHA = 'manha';
    public const TARDE = 'tarde';

    /** Até este intervalo dois horários contam como vizinhos (capitanias com vagas de 30 e de 60 min). */
    private const MINUTOS_VIZINHOS = 60;

    /**
     * @param  array<int, array{data: string, hora: string, turno?: string}>  $horarios  data em Y-m-d, hora em H:i,
     *                                                                                  turno manha|tarde (opcional);
     *                                                                                  chaves extras voltam intactas
     * @return array|null  o item escolhido, exatamente como veio
     */
    public static function escolher(
        array $horarios,
        ?DateTimeInterface $referencia = null,
        ?DateTimeInterface $dataSugerida = null,
        ?string $periodo = null,
    ): ?array {
        $candidatos = static::candidatos($horarios);

        if ($candidatos === []) {
            return null;
        }

        if ($referencia) {
            return static::comReferencia($candidatos, CarbonImmutable::instance($referencia))['horario'];
        }

        $periodo = static::periodoValido($periodo);
        $par = static::melhorPar($candidatos, $dataSugerida, $periodo);

        return $par
            ? static::primeiroDoPar($par, $periodo, static::base($candidatos, $dataSugerida))['horario']
            : $candidatos[0]['horario'];
    }

    /**
     * Os dois horários do melhor par (na ordem em que devem ser marcados), para exibir o plano.
     *
     * @return array{0: array, 1: array}|null
     */
    public static function planejarPar(array $horarios, ?DateTimeInterface $dataSugerida = null, ?string $periodo = null): ?array
    {
        $periodo = static::periodoValido($periodo);
        $candidatos = static::candidatos($horarios);
        $par = static::melhorPar($candidatos, $dataSugerida, $periodo);

        if (!$par) {
            return null;
        }

        $primeiro = static::primeiroDoPar($par, $periodo, static::base($candidatos, $dataSugerida));
        $segundo = $primeiro === $par[0] ? $par[1] : $par[0];

        return [$primeiro['horario'], $segundo['horario']];
    }

    private static function comReferencia(array $candidatos, CarbonImmutable $referencia): array
    {
        $turnoReferencia = static::turnoDaHora((int) $referencia->format('H'));

        return static::menor($candidatos, fn(array $c) => [
            $c['momento']->isSameDay($referencia) ? 0 : 1,
            $c['turno'] === $turnoReferencia ? 0 : 1,
            abs($c['momento']->getTimestamp() - $referencia->getTimestamp()),
            $c['momento']->getTimestamp(),
            $c['posicao'],
        ]);
    }

    /**
     * @return array{0: array, 1: array}|null  o par, com o horário mais cedo primeiro
     */
    private static function melhorPar(array $candidatos, ?DateTimeInterface $dataSugerida, ?string $periodo): ?array
    {
        if (count($candidatos) < 2) {
            return null;
        }

        usort($candidatos, fn(array $a, array $b) => [$a['momento']->getTimestamp(), $a['posicao']] <=> [$b['momento']->getTimestamp(), $b['posicao']]);

        $base = static::base($candidatos, $dataSugerida);
        $melhor = null;
        $melhorChave = null;
        $total = count($candidatos);

        for ($i = 0; $i < $total; $i++) {
            for ($j = $i + 1; $j < $total; $j++) {
                $a = $candidatos[$i];
                $b = $candidatos[$j];

                $mesmaData = $a['momento']->isSameDay($b['momento']);
                $mesmoPeriodo = $a['turno'] === $b['turno'];
                $ambosNoPeriodo = $periodo ? ($a['turno'] === $periodo && $b['turno'] === $periodo) : $mesmoPeriodo;
                $algumNoPeriodo = !$periodo || $a['turno'] === $periodo || $b['turno'] === $periodo;
                $intervalo = intdiv($b['momento']->getTimestamp() - $a['momento']->getTimestamp(), 60);

                $chave = [
                    $mesmaData ? ($ambosNoPeriodo ? 0 : 1) : ($ambosNoPeriodo ? 2 : 3),
                    $mesmaData
                        ? static::distanciaEmDias($a['momento'], $base)
                        : static::distanciaEmDias($a['momento'], $base) + static::distanciaEmDias($b['momento'], $base),
                    $mesmoPeriodo ? 0 : 1,
                    $algumNoPeriodo ? 0 : 1,
                    $mesmaData && $intervalo <= self::MINUTOS_VIZINHOS ? 0 : 1,
                    $intervalo,
                    $a['momento']->getTimestamp(),
                ];

                if ($melhorChave === null || $chave < $melhorChave) {
                    $melhor = [$a, $b];
                    $melhorChave = $chave;
                }
            }
        }

        return $melhor;
    }

    /**
     * Qual horário do par marcar agora: o que está no período escolhido (garante ao menos um no período),
     * depois o da data mais próxima da sugerida, depois o mais cedo.
     */
    private static function primeiroDoPar(array $par, ?string $periodo, CarbonImmutable $base): array
    {
        return static::menor($par, fn(array $c) => [
            $periodo && $c['turno'] !== $periodo ? 1 : 0,
            static::distanciaEmDias($c['momento'], $base),
            $c['momento']->getTimestamp(),
            $c['posicao'],
        ]);
    }

    private static function candidatos(array $horarios): array
    {
        $candidatos = [];

        foreach (array_values($horarios) as $posicao => $horario) {
            $momento = static::momento($horario);

            if (!$momento) {
                continue;
            }

            $turno = static::periodoValido($horario['turno'] ?? null) ?? static::turnoDaHora((int) $momento->format('H'));

            $candidatos[] = compact('horario', 'momento', 'turno', 'posicao');
        }

        return $candidatos;
    }

    private static function menor(array $candidatos, callable $chave): array
    {
        usort($candidatos, fn(array $a, array $b) => $chave($a) <=> $chave($b));

        return $candidatos[0];
    }

    /** Dia de partida para medir "mais próxima": a data sugerida ou, sem ela, o dia mais cedo disponível. */
    private static function base(array $candidatos, ?DateTimeInterface $dataSugerida): CarbonImmutable
    {
        if ($dataSugerida) {
            return CarbonImmutable::instance($dataSugerida)->startOfDay();
        }

        return collect($candidatos)->map(fn(array $c) => $c['momento']->startOfDay())->sort()->first();
    }

    private static function distanciaEmDias(CarbonImmutable $momento, CarbonImmutable $base): int
    {
        return (int) round(abs($momento->startOfDay()->diffInDays($base, false)));
    }

    private static function turnoDaHora(int $hora): string
    {
        return $hora < 12 ? self::MANHA : self::TARDE;
    }

    private static function periodoValido(?string $periodo): ?string
    {
        return in_array($periodo, [self::MANHA, self::TARDE], true) ? $periodo : null;
    }

    private static function momento(mixed $horario): ?CarbonImmutable
    {
        if (!is_array($horario) || !isset($horario['data'], $horario['hora'])) {
            return null;
        }

        $texto = "{$horario['data']} {$horario['hora']}";

        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $texto)) {
            return null;
        }

        $momento = CarbonImmutable::createFromFormat('!Y-m-d H:i', $texto);

        // createFromFormat aceita 2026-02-31 e "rola" para março; isso não é um horário válido.
        return $momento && $momento->format('Y-m-d H:i') === $texto ? $momento : null;
    }
}
