<?php

namespace Tests\Unit;

use App\Support\OrganizacoesMilitaresSisap;
use PHPUnit\Framework\TestCase;

class OrganizacoesMilitaresSisapTest extends TestCase
{
    public function test_lista_tem_as_72_oms_levantadas_no_sisap(): void
    {
        $lista = OrganizacoesMilitaresSisap::lista();

        $this->assertCount(72, $lista);
        $this->assertSame(count($lista), count(array_unique($lista)), 'nomes repetidos');
        $this->assertArrayNotHasKey(1, $lista, 'o item "Selecione a Organização Militar" não é uma OM');
    }

    public function test_codigos_das_capitanias_usadas_no_proa(): void
    {
        $this->assertSame('Capitania Fluvial de Goiás', OrganizacoesMilitaresSisap::nome(136));
        $this->assertSame('Capitania Fluvial de Brasília', OrganizacoesMilitaresSisap::nome('63'));
        $this->assertSame('Capitania Fluvial de Minas Gerais', OrganizacoesMilitaresSisap::nome(133));
        $this->assertSame('Capitania Fluvial de Mato Grosso', OrganizacoesMilitaresSisap::nome(78));
        $this->assertSame('Capitania Fluvial do Araguaia Tocantins', OrganizacoesMilitaresSisap::nome(42));
        $this->assertSame('Capitania Fluvial do Pantanal', OrganizacoesMilitaresSisap::nome(76));
    }

    public function test_codigo_desconhecido_ou_vazio(): void
    {
        $this->assertNull(OrganizacoesMilitaresSisap::nome(9999));
        $this->assertNull(OrganizacoesMilitaresSisap::nome(null));
        $this->assertNull(OrganizacoesMilitaresSisap::nome('abc'));
    }
}
