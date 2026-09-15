<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

/**
 * Regras do administrador sem banco: reconhecimento pelo e-mail e acesso às áreas restritas.
 */
class AdministradorTest extends TestCase
{
    public function test_reconhece_o_administrador_ignorando_maiusculas_e_espacos(): void
    {
        config(['proa.administradores' => ['marcoanunes23@gmail.com']]);

        $this->assertTrue((new User(['email' => 'marcoanunes23@gmail.com']))->ehAdministrador());
        $this->assertTrue((new User(['email' => '  MarcoAnunes23@Gmail.COM ']))->ehAdministrador());
        $this->assertFalse((new User(['email' => 'outro@gmail.com']))->ehAdministrador());
        $this->assertFalse((new User(['email' => null]))->ehAdministrador());
    }

    public function test_administradores_configurados_no_env(): void
    {
        config(['proa.administradores' => ['admin@proa.com.br', 'marcoanunes23@gmail.com']]);

        $this->assertTrue((new User(['email' => 'ADMIN@proa.com.br']))->ehAdministrador());
        $this->assertTrue((new User(['email' => 'marcoanunes23@gmail.com']))->ehAdministrador());
    }

    public function test_sem_configuracao_usa_o_email_padrao(): void
    {
        config(['proa.administradores' => []]);

        $this->assertTrue((new User(['email' => User::EMAIL_ADMINISTRADOR]))->ehAdministrador());
    }

    public function test_administrador_entra_em_todas_as_areas_mesmo_sem_as_opcoes_marcadas(): void
    {
        config(['proa.administradores' => ['marcoanunes23@gmail.com']]);

        $admin = new User([
            'email' => 'MarcoAnunes23@gmail.com',
            'pode_acessar_financeiro' => false,
            'pode_gerenciar_usuarios' => false,
            'pode_acessar_agendamento' => false,
        ]);

        $this->assertTrue($admin->podeAcessarFinanceiro());
        $this->assertTrue($admin->podeGerenciarUsuarios());
        $this->assertTrue($admin->podeAcessarAgendamento());
        $this->assertTrue($admin->podeConcederPermissoes());
    }

    public function test_usuario_comum_depende_das_opcoes(): void
    {
        config(['proa.administradores' => ['marcoanunes23@gmail.com']]);

        $comum = new User(['email' => 'equipe@gmail.com', 'pode_acessar_financeiro' => true]);

        $this->assertTrue($comum->podeAcessarFinanceiro());
        $this->assertFalse($comum->podeGerenciarUsuarios());
        $this->assertFalse($comum->podeAcessarAgendamento());
        $this->assertFalse($comum->podeConcederPermissoes());
    }
}
