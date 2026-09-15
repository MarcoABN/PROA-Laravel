<?php

namespace App\Support;

/**
 * Organizações Militares disponíveis no agendamento eletrônico do SISAP e o código (nidom) de cada uma.
 *
 * Levantado em 16/09/2026 da lista que o próprio SISAP carrega na tela de seleção de OM
 * (mainpage.php). Se a Marinha incluir ou renomear OMs, atualize esta lista.
 */
class OrganizacoesMilitaresSisap
{
    public const LEVANTADO_EM = '16/09/2026';

    private const LISTA = [
        92 => 'Agência da Capitania dos Portos em Aracati',
        96 => 'Agência da Capitania dos Portos em Areia Branca',
        91 => 'Agência da Capitania dos Portos em Camocim',
        22 => 'Agência da Capitania dos Portos em Paraty',
        120 => 'Agência da Capitania dos Portos em São João da Barra',
        105 => 'Agência da Capitania dos Portos em Tramandaí',
        134 => 'Agência da Capitania dos Portos no Oiapoque',
        110 => 'Agência Fluvial de Boca do Acre',
        28 => 'Agência Fluvial de Bom Jesus da Lapa',
        77 => 'Agência Fluvial de Cáceres',
        132 => 'Agência Fluvial de Caracaraí',
        118 => 'Agência Fluvial de Cruzeiro do Sul',
        119 => 'Agência Fluvial de Eirunepé',
        112 => 'Agência Fluvial de Guajará-Mirim',
        117 => 'Agência Fluvial de Humaitá',
        40 => 'Agência Fluvial de Imperatriz',
        113 => 'Agência Fluvial de Itacoatiara',
        116 => 'Agência Fluvial de Parintins',
        89 => 'Agência Fluvial de Penedo',
        79 => 'Agência Fluvial de Porto Murtinho',
        41 => 'Agência Fluvial de São Felix do Araguaia',
        155 => 'Agência Fluvial de Sinop',
        115 => 'Agência Fluvial de Tefé',
        80 => 'Capitania dos Portos da Amazônia Oriental',
        25 => 'Capitania dos Portos da Bahia',
        93 => 'Capitania dos Portos da Paraíba',
        88 => 'Capitania dos Portos de Alagoas',
        20 => 'Capitania dos Portos de Macaé',
        94 => 'Capitania dos Portos de Pernambuco',
        98 => 'Capitania dos Portos de Santa Catarina',
        64 => 'Capitania dos Portos de São Paulo',
        27 => 'Capitania dos Portos de Sergipe',
        81 => 'Capitania dos Portos do Amapá',
        90 => 'Capitania dos Portos do Ceará',
        17 => 'Capitania dos Portos do Espírito Santo',
        83 => 'Capitania dos Portos do Maranhão',
        97 => 'Capitania dos Portos do Paraná',
        84 => 'Capitania dos Portos do Piauí',
        18 => 'Capitania dos Portos do Rio de Janeiro',
        95 => 'Capitania dos Portos do Rio Grande do Norte',
        103 => 'Capitania dos Portos do Rio Grande do Sul',
        109 => 'Capitania Fluvial da Amazônia Ocidental',
        63 => 'Capitania Fluvial de Brasília',
        136 => 'Capitania Fluvial de Goiás',
        29 => 'Capitania Fluvial de Juazeiro',
        78 => 'Capitania Fluvial de Mato Grosso',
        133 => 'Capitania Fluvial de Minas Gerais',
        108 => 'Capitania Fluvial de Porto Alegre',
        111 => 'Capitania Fluvial de Porto Velho',
        87 => 'Capitania Fluvial de Santarém',
        114 => 'Capitania Fluvial de Tabatinga',
        42 => 'Capitania Fluvial do Araguaia Tocantins',
        76 => 'Capitania Fluvial do Pantanal',
        100 => 'Capitania Fluvial do Rio Paraná',
        75 => 'Capitania Fluvial do Tietê-Paraná',
        85 => 'Centro de Instrução Almirante Braz de Aguiar',
        39 => 'Centro de Instrução Almirante Graça Aranha',
        130 => 'Comando do 8º Distrito Naval',
        19 => 'Delegacia da Capitania dos Portos em Angra dos Reis',
        23 => 'Delegacia da Capitania dos Portos em Cabo Frio',
        26 => 'Delegacia da Capitania dos Portos em Ilhéus',
        21 => 'Delegacia da Capitania dos Portos em Itacuruçá',
        101 => 'Delegacia da Capitania dos Portos em Itajaí',
        102 => 'Delegacia da Capitania dos Portos em Laguna',
        24 => 'Delegacia da Capitania dos Portos em Porto Seguro',
        99 => 'Delegacia da Capitania dos Portos em São Francisco do Sul',
        66 => 'Delegacia da Capitania dos Portos em São Sebastião',
        135 => 'Delegacia Fluvial de Furnas',
        106 => 'Delegacia Fluvial de Guaíra',
        30 => 'Delegacia Fluvial de Pirapora',
        65 => 'Delegacia Fluvial de Presidente Epitácio',
        107 => 'Delegacia Fluvial de Uruguaiana',
    ];

    /** @return array<int, string> código => nome, em ordem alfabética de nome */
    public static function lista(): array
    {
        return self::LISTA;
    }

    public static function nome(mixed $codigo): ?string
    {
        return is_numeric($codigo) ? (self::LISTA[(int) $codigo] ?? null) : null;
    }
}
