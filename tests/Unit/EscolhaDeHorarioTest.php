<?php

namespace Tests\Unit;

use App\Services\Agendamento\EscolhaDeHorario;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class EscolhaDeHorarioTest extends TestCase
{
    public function test_sem_referencia_pega_o_primeiro_horario_livre(): void
    {
        $escolhido = EscolhaDeHorario::escolher([
            ['data' => '2026-09-22', 'hora' => '08:30'],
            ['data' => '2026-09-21', 'hora' => '14:00'],
            ['data' => '2026-09-21', 'hora' => '10:30'],
        ]);

        $this->assertSame(['data' => '2026-09-21', 'hora' => '10:30'], $escolhido);
    }

    public function test_com_referencia_prioriza_mesma_data_no_horario_mais_proximo(): void
    {
        $escolhido = EscolhaDeHorario::escolher([
            ['data' => '2026-09-20', 'hora' => '08:30'],
            ['data' => '2026-09-20', 'hora' => '15:30'],
            ['data' => '2026-09-20', 'hora' => '17:00'],
        ], CarbonImmutable::parse('2026-09-20 15:00'));

        $this->assertSame('15:30', $escolhido['hora']);
    }

    public function test_mesma_data_ganha_de_outra_data_mesmo_mais_distante(): void
    {
        $escolhido = EscolhaDeHorario::escolher([
            ['data' => '2026-09-21', 'hora' => '15:00'],
            ['data' => '2026-09-20', 'hora' => '08:00'],
        ], CarbonImmutable::parse('2026-09-20 15:00'));

        $this->assertSame(['data' => '2026-09-20', 'hora' => '08:00'], $escolhido);
    }

    public function test_empate_de_distancia_fica_com_o_mais_cedo(): void
    {
        $escolhido = EscolhaDeHorario::escolher([
            ['data' => '2026-09-20', 'hora' => '15:30'],
            ['data' => '2026-09-20', 'hora' => '14:30'],
        ], CarbonImmutable::parse('2026-09-20 15:00'));

        $this->assertSame('14:30', $escolhido['hora']);
    }

    public function test_sem_vaga_na_data_usa_o_horario_mais_proximo_em_outra_data(): void
    {
        $escolhido = EscolhaDeHorario::escolher([
            ['data' => '2026-09-24', 'hora' => '08:00'],
            ['data' => '2026-09-21', 'hora' => '09:00'],
        ], CarbonImmutable::parse('2026-09-20 15:00'));

        $this->assertSame(['data' => '2026-09-21', 'hora' => '09:00'], $escolhido);
    }

    public function test_devolve_chaves_extras_e_ignora_itens_invalidos(): void
    {
        $escolhido = EscolhaDeHorario::escolher([
            ['data' => '2026-02-31', 'hora' => '08:00'],
            ['data' => '21/09/2026', 'hora' => '08:00'],
            ['hora' => '08:00'],
            ['data' => '2026-09-23', 'hora' => '14:00', 'token' => 'abc'],
        ]);

        $this->assertSame(['data' => '2026-09-23', 'hora' => '14:00', 'token' => 'abc'], $escolhido);
    }

    public function test_lista_vazia_devolve_nulo(): void
    {
        $this->assertNull(EscolhaDeHorario::escolher([]));
    }

    // --- Par de agendamentos com data sugerida e período ---

    private static function h(string $data, string $hora): array
    {
        return ['data' => $data, 'hora' => $hora];
    }

    private static function plano(array $horarios, ?string $data, ?string $periodo): array
    {
        $par = EscolhaDeHorario::planejarPar($horarios, $data ? CarbonImmutable::parse($data) : null, $periodo);

        return array_map(fn(array $h) => "{$h['data']} {$h['hora']}", $par);
    }

    public function test_cenario_perfeito_horarios_vizinhos_de_30_min_na_data_sugerida(): void
    {
        $horarios = [
            self::h('2026-10-19', '15:00'), self::h('2026-10-19', '15:30'),
            self::h('2026-10-20', '08:00'), self::h('2026-10-20', '14:00'),
            self::h('2026-10-20', '15:00'), self::h('2026-10-20', '15:30'), self::h('2026-10-20', '17:00'),
        ];

        $this->assertSame(['2026-10-20 15:00', '2026-10-20 15:30'], self::plano($horarios, '2026-10-20', EscolhaDeHorario::TARDE));
        $this->assertSame('15:00', EscolhaDeHorario::escolher($horarios, null, CarbonImmutable::parse('2026-10-20'), 'tarde')['hora']);
    }

    public function test_cenario_perfeito_em_capitania_de_60_min(): void
    {
        $horarios = [self::h('2026-10-20', '08:00'), self::h('2026-10-20', '14:00'), self::h('2026-10-20', '16:00'), self::h('2026-10-20', '17:00')];

        $this->assertSame(['2026-10-20 16:00', '2026-10-20 17:00'], self::plano($horarios, '2026-10-20', 'tarde'));
    }

    public function test_cenario_ideal_mesmo_periodo_com_horarios_distantes(): void
    {
        $horarios = [self::h('2026-10-20', '08:00'), self::h('2026-10-20', '14:00'), self::h('2026-10-20', '17:00'), self::h('2026-10-21', '14:00'), self::h('2026-10-21', '14:30')];

        // A data sugerida (mesmo com horários distantes) vence a vizinhança perfeita em outra data.
        $this->assertSame(['2026-10-20 14:00', '2026-10-20 17:00'], self::plano($horarios, '2026-10-20', 'tarde'));
    }

    public function test_um_so_horario_no_periodo_leva_para_a_data_mais_proxima_com_dois(): void
    {
        $horarios = [
            self::h('2026-10-20', '09:00'), self::h('2026-10-20', '15:00'), // sugerida: só 1 à tarde
            self::h('2026-10-19', '14:00'), self::h('2026-10-19', '14:30'), // anterior, distância 1
            self::h('2026-10-22', '15:00'), self::h('2026-10-22', '15:30'), // posterior, distância 2
        ];

        $this->assertSame(['2026-10-19 14:00', '2026-10-19 14:30'], self::plano($horarios, '2026-10-20', 'tarde'));
    }

    public function test_sem_data_com_dois_no_periodo_mantem_mesma_data_e_marca_primeiro_o_do_periodo(): void
    {
        $horarios = [self::h('2026-10-20', '09:00'), self::h('2026-10-20', '15:00'), self::h('2026-10-23', '16:00')];

        $this->assertSame(['2026-10-20 15:00', '2026-10-20 09:00'], self::plano($horarios, '2026-10-20', 'tarde'));
        $this->assertSame('15:00', EscolhaDeHorario::escolher($horarios, null, CarbonImmutable::parse('2026-10-20'), 'tarde')['hora']);
    }

    public function test_periodo_matutino_escolhe_manha_mesmo_com_tarde_na_data(): void
    {
        $horarios = [self::h('2026-10-20', '08:00'), self::h('2026-10-20', '08:30'), self::h('2026-10-20', '14:00'), self::h('2026-10-20', '14:30')];

        $this->assertSame(['2026-10-20 08:00', '2026-10-20 08:30'], self::plano($horarios, '2026-10-20', 'manha'));
    }

    public function test_sem_preferencias_o_par_fica_na_data_mais_cedo_com_dois_no_mesmo_periodo(): void
    {
        $horarios = [self::h('2026-10-05', '09:00'), self::h('2026-10-05', '15:00'), self::h('2026-10-06', '10:00'), self::h('2026-10-06', '11:00')];

        // Dia 5 tem dois horários, mas em períodos diferentes; dia 6 comporta ambos de manhã.
        $this->assertSame(['2026-10-06 10:00', '2026-10-06 11:00'], self::plano($horarios, null, null));
    }

    public function test_sem_data_com_espaco_para_dois_fica_no_periodo_e_na_data_mais_proxima(): void
    {
        // Nenhuma data tem dois horários: fica o horário da tarde mais próximo da data sugerida.
        $horarios = [self::h('2026-10-20', '09:00'), self::h('2026-10-18', '16:00'), self::h('2026-10-21', '14:00')];

        $escolhido = EscolhaDeHorario::escolher($horarios, null, CarbonImmutable::parse('2026-10-20'), 'tarde');

        $this->assertSame(['data' => '2026-10-21', 'hora' => '14:00'], $escolhido);
    }

    public function test_data_indisponivel_troca_por_data_com_espaco_para_dois_no_periodo(): void
    {
        // Cenário do teste em Mato Grosso: preferência 25/09 à tarde, dia 25 sem vaga.
        // O dia 24 é o mais próximo, mas só tem um horário à tarde; o 23 comporta dois.
        $horarios = [
            ['data' => '2026-09-21', 'hora' => '10:30', 'turno' => 'manha'], ['data' => '2026-09-21', 'hora' => '14:00', 'turno' => 'tarde'],
            ['data' => '2026-09-23', 'hora' => '14:00', 'turno' => 'tarde'], ['data' => '2026-09-23', 'hora' => '15:00', 'turno' => 'tarde'],
            ['data' => '2026-09-24', 'hora' => '08:30', 'turno' => 'manha'], ['data' => '2026-09-24', 'hora' => '09:30', 'turno' => 'manha'],
            ['data' => '2026-09-24', 'hora' => '10:30', 'turno' => 'manha'], ['data' => '2026-09-24', 'hora' => '14:00', 'turno' => 'tarde'],
            ['data' => '2026-09-28', 'hora' => '14:00', 'turno' => 'tarde'], ['data' => '2026-09-28', 'hora' => '14:30', 'turno' => 'tarde'],
        ];

        // Mesmo com um único agendamento do procurador até agora.
        $escolhido = EscolhaDeHorario::escolher($horarios, null, CarbonImmutable::parse('2026-09-25'), 'tarde');

        $this->assertSame('2026-09-23 14:00', "{$escolhido['data']} {$escolhido['hora']}");
    }

    public function test_com_agendamento_ja_marcado_no_periodo_aceita_horario_unico(): void
    {
        // O primeiro já está em 24/09 às 14:00: o segundo pode ficar num horário único da mesma data e período.
        $horarios = [self::h('2026-09-23', '14:00'), self::h('2026-09-23', '15:00'), self::h('2026-09-24', '08:30'), self::h('2026-09-24', '15:00')];

        $escolhido = EscolhaDeHorario::escolher($horarios, CarbonImmutable::parse('2026-09-24 14:00'), CarbonImmutable::parse('2026-09-25'), 'tarde');

        $this->assertSame('2026-09-24 15:00', "{$escolhido['data']} {$escolhido['hora']}");
    }

    public function test_um_unico_horario_livre_e_escolhido(): void
    {
        $this->assertSame('09:00', EscolhaDeHorario::escolher([self::h('2026-10-20', '09:00')], null, CarbonImmutable::parse('2026-10-25'), 'tarde')['hora']);
    }

    public function test_segundo_agendamento_segue_a_referencia_no_mesmo_periodo(): void
    {
        $horarios = [self::h('2026-10-20', '11:30'), self::h('2026-10-20', '16:00'), self::h('2026-10-21', '15:30')];

        // Referência 15:00: 11:30 está mais perto, mas é de manhã; fica 16:00 na mesma data e período.
        $escolhido = EscolhaDeHorario::escolher($horarios, CarbonImmutable::parse('2026-10-20 15:00'), CarbonImmutable::parse('2026-10-22'), 'tarde');

        $this->assertSame('2026-10-20 16:00', "{$escolhido['data']} {$escolhido['hora']}");
    }

    public function test_turno_informado_pelo_sisap_prevalece_sobre_a_hora(): void
    {
        // Capitania que classifica 12:00 como manhã.
        $horarios = [['data' => '2026-10-20', 'hora' => '12:00', 'turno' => 'manha'], self::h('2026-10-20', '14:00')];

        $this->assertSame('12:00', EscolhaDeHorario::escolher($horarios, null, null, 'manha')['hora']);
    }
}
