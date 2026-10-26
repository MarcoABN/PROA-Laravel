<?php

namespace Tests\Unit;

use App\Services\Agendamento\VisibilidadeNaExtensao;
use PHPUnit\Framework\TestCase;

class AberturaDaAgendaTest extends TestCase
{
    public function test_agenda_abre_no_ultimo_dia_util_do_mes_anterior(): void
    {
        // 30/11/2026 é segunda-feira.
        $this->assertSame('2026-11-30', VisibilidadeNaExtensao::abertura('2026-12-01')->toDateString());
        // 31/10/2026 é sábado: volta para sexta.
        $this->assertSame('2026-10-30', VisibilidadeNaExtensao::abertura('2026-11-01')->toDateString());
    }

    public function test_feriado_no_fim_do_mes_passa_para_o_dia_util_anterior(): void
    {
        // Carnaval de 2028 cai em 28 e 29/02 (segunda e terça); 26 e 27 são fim de semana.
        $this->assertSame('2028-02-25', VisibilidadeNaExtensao::abertura('2028-03-01')->toDateString());
    }
}
