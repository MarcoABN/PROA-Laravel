<?php

namespace Tests\Unit;

use App\Support\Feriados;
use PHPUnit\Framework\TestCase;

class FeriadosTest extends TestCase
{
    public function test_calcula_pascoa(): void
    {
        $this->assertSame('2025-04-20', Feriados::pascoa(2025)->toDateString());
        $this->assertSame('2026-04-05', Feriados::pascoa(2026)->toDateString());
        $this->assertSame('2027-03-28', Feriados::pascoa(2027)->toDateString());
    }

    public function test_feriados_de_2026(): void
    {
        $feriados = Feriados::doAno(2026);

        $this->assertSame('Carnaval', $feriados['2026-02-16']);
        $this->assertSame('Carnaval', $feriados['2026-02-17']);
        $this->assertSame('Sexta-feira Santa', $feriados['2026-04-03']);
        $this->assertSame('Corpus Christi', $feriados['2026-06-04']);
        $this->assertSame('Nossa Senhora Aparecida', $feriados['2026-10-12']);
        $this->assertSame('Dia da Consciência Negra', $feriados['2026-11-20']);
        $this->assertCount(13, $feriados);
    }

    public function test_junta_anos(): void
    {
        $feriados = Feriados::dosAnos(2026, 2027);

        $this->assertArrayHasKey('2026-12-25', $feriados);
        $this->assertArrayHasKey('2027-01-01', $feriados);
    }
}
