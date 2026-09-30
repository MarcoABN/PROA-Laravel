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

    public function test_valida_digitos_verificadores_do_cnpj(): void
    {
        $this->assertTrue(SolicitacaoAgendamento::cnpjValido('11.222.333/0001-81'));
        $this->assertTrue(SolicitacaoAgendamento::cnpjValido('11444777000161'));

        $this->assertFalse(SolicitacaoAgendamento::cnpjValido('11.222.333/0001-82'));
        $this->assertFalse(SolicitacaoAgendamento::cnpjValido('11.111.111/1111-11'));
        $this->assertFalse(SolicitacaoAgendamento::cnpjValido('52998224725'));
        $this->assertFalse(SolicitacaoAgendamento::cnpjValido(null));
    }

    public function test_documento_aceita_cpf_ou_cnpj(): void
    {
        $this->assertTrue(SolicitacaoAgendamento::documentoValido('529.982.247-25'));
        $this->assertTrue(SolicitacaoAgendamento::documentoValido('11.222.333/0001-81'));
        $this->assertFalse(SolicitacaoAgendamento::documentoValido('1122233300018'));

        $this->assertSame('CPF', SolicitacaoAgendamento::tipoDocumento('529.982.247-25'));
        $this->assertSame('CNPJ', SolicitacaoAgendamento::tipoDocumento('11.222.333/0001-81'));
        $this->assertNull(SolicitacaoAgendamento::tipoDocumento('123'));
    }

    public function test_formata_cpf_e_cnpj(): void
    {
        $this->assertSame('529.982.247-25', SolicitacaoAgendamento::formatarDocumento('52998224725'));
        $this->assertSame('11.222.333/0001-81', SolicitacaoAgendamento::formatarDocumento('11222333000181'));
        $this->assertSame('123', SolicitacaoAgendamento::formatarDocumento('123'));
    }
}
