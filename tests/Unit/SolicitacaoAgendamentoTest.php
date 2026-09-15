<?php

namespace Tests\Unit;

use App\Models\SolicitacaoAgendamento;
use PHPUnit\Framework\TestCase;

class SolicitacaoAgendamentoTest extends TestCase
{
    public function test_valida_digitos_verificadores_do_cpf(): void
    {
        $this->assertTrue(SolicitacaoAgendamento::cpfValido('529.982.247-25'));
        $this->assertTrue(SolicitacaoAgendamento::cpfValido('52998224725'));

        $this->assertFalse(SolicitacaoAgendamento::cpfValido('529.982.247-24'));
        $this->assertFalse(SolicitacaoAgendamento::cpfValido('111.111.111-11'));
        $this->assertFalse(SolicitacaoAgendamento::cpfValido('5299822472'));
        $this->assertFalse(SolicitacaoAgendamento::cpfValido(null));
    }

    public function test_formata_cpf(): void
    {
        $this->assertSame('529.982.247-25', SolicitacaoAgendamento::formatarCpf('52998224725'));
        $this->assertSame('123', SolicitacaoAgendamento::formatarCpf('123'));
    }
}
