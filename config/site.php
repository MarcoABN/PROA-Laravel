<?php

/*
|--------------------------------------------------------------------------
| Site público (campeaonautica.com.br)
|--------------------------------------------------------------------------
| Dados de contato e conteúdo fixo usados nas páginas públicas e nos dados
| estruturados (schema.org). Alterar aqui reflete no site inteiro.
*/

return [

    'nome' => 'Campeão Náutica',
    'url' => 'https://campeaonautica.com.br',
    'descricao' => 'Despachante náutico e escola de navegação em Goiânia. Habilitação de Arrais Amador e Motonauta em Goiás e regularização de embarcações em todo o Brasil.',

    'whatsapp' => 'https://wa.me/5562998599357',
    'whatsapp_exibicao' => '(62) 9 9859-9357',
    'telefone' => '+5562998599357',
    'telefone2' => '+5562996577973',
    'telefone2_exibicao' => '(62) 9 9657-7973',
    'email' => 'contato@campeaonautica.com.br',
    'email2' => 'campeaonautica@gmail.com',
    'instagram' => 'https://www.instagram.com/campeao.despachantenautico10',
    'instagram_usuario' => '@campeao.despachantenautico10',
    'cnpj' => '53.775.360/0001-21',

    'endereco' => [
        'rua' => 'Avenida 24 de Outubro, 3053',
        'complemento' => 'Quadra 17 Lote 28, Bairro Aeroviário',
        'cidade' => 'Goiânia',
        'uf' => 'GO',
        'cep' => '74435-090',
        'latitude' => -16.6710,
        'longitude' => -49.2845,
        // Texto buscado no Google Maps (com o nome, abre direto o Perfil da Empresa)
        'mapa_busca' => 'Campeão Náutica, Avenida 24 de Outubro, 3053, Aeroviário, Goiânia - GO, 74435-090',
    ],

    // Perguntas frequentes da página inicial (também geram o schema FAQPage)
    'faq' => [
        [
            'pergunta' => 'Vocês atendem clientes de fora de Goiânia?',
            'resposta' => 'Sim. Preparamos alunos de todo o estado de Goiás para Arrais Amador e Motonauta e cuidamos da regularização de embarcações em qualquer estado do Brasil.',
        ],
        [
            'pergunta' => 'Qual a diferença entre Arrais Amador e Motonauta?',
            'resposta' => 'O Arrais Amador habilita a conduzir embarcações de esporte e recreio, como lanchas e barcos, em águas interiores (rios, lagos e represas). O Motonauta habilita a conduzir motos aquáticas, como o jet ski.',
        ],
        [
            'pergunta' => 'Como funciona o simulado online?',
            'resposta' => 'Nossos alunos acessam a Área do Cliente com o CPF e treinam com questões no formato da prova da Marinha antes do exame.',
        ],
        [
            'pergunta' => 'Quanto tempo leva para regularizar uma embarcação?',
            'resposta' => 'Depende do serviço e da agenda da Capitania responsável. Depois de analisar os documentos do seu caso, informamos o prazo estimado.',
        ],
        [
            'pergunta' => 'Como peço um orçamento?',
            'resposta' => 'Pelo WhatsApp (62) 9 9859-9357, pelo telefone (62) 9 9657-7973 ou no escritório, na Avenida 24 de Outubro, 3053, em Goiânia, de segunda a sexta, das 8h às 18h.',
        ],
    ],

];
