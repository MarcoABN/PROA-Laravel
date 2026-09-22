<?php

namespace Database\Seeders;

use App\Models\Servico;
use Illuminate\Database\Seeder;

/**
 * Conteúdo das páginas públicas de serviço (/servicos/{slug}).
 *
 * Só preenche campos que estão vazios, para não sobrescrever o que já foi
 * editado no PROA. Também remove o "WhatsApp: 📞 ..." do fim das descrições,
 * que cortava o texto exibido no Google.
 *
 * php artisan db:seed --class=ServicosConteudoSeeder
 */
class ServicosConteudoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::conteudo() as $slug => $dados) {
            $servico = Servico::where('slug', $slug)->first();

            if (! $servico) {
                $this->command?->warn("Serviço não encontrado: {$slug}");
                continue;
            }

            foreach (['titulo_seo', 'meta_descricao', 'conteudo', 'faq'] as $campo) {
                if (blank($servico->{$campo})) {
                    $servico->{$campo} = $dados[$campo];
                }
            }

            $servico->descricao = trim(preg_replace('/\s*WhatsApp:.*$/u', '', $servico->descricao));

            if ($servico->isDirty()) {
                $servico->save();
                $this->command?->info("Atualizado: {$slug} (" . implode(', ', array_keys($servico->getChanges())) . ')');
            } else {
                $this->command?->line("Sem alterações: {$slug}");
            }
        }
    }

    public static function conteudo(): array
    {
        return [

            'habilitacao' => [
                'titulo_seo' => 'Arrais Amador e Motonauta em Goiânia | Habilitação Náutica',
                'meta_descricao' => 'Tire, renove ou peça a 2ª via da sua habilitação náutica de Arrais Amador ou Motonauta. Escola náutica em Goiânia com alunos de todo o estado de Goiás.',
                'conteudo' => <<<'HTML'
<h2>Habilitação náutica em Goiânia</h2>
<p>Para conduzir uma lancha, um barco de passeio ou um jet ski em rios, lagos e represas, a Marinha do Brasil exige a Carteira de Habilitação de Amador (CHA). A Campeão Náutica prepara você para a prova e cuida de toda a documentação, da inscrição até a entrega da carteira.</p>
<p>Atendemos alunos de Goiânia e de todo o estado de Goiás. Você não precisa entender de norma nem enfrentar fila na Capitania: nós dizemos o que separar e cuidamos do resto.</p>
<h2>Qual categoria você precisa?</h2>
<ul>
<li><strong>Arrais Amador</strong>: para conduzir embarcações de esporte e recreio, como lanchas e barcos, em águas interiores.</li>
<li><strong>Motonauta</strong>: para conduzir motos aquáticas, como o jet ski.</li>
</ul>
<p>Quem usa os dois tipos de embarcação pode ter as duas categorias. Se estiver em dúvida, conte como pretende navegar e indicamos a habilitação certa para o seu caso.</p>
<h2>O que fazemos por você</h2>
<ul>
<li>Orientação sobre a categoria e os documentos necessários</li>
<li>Preparação para a prova, com simulado online no formato do exame da Marinha</li>
<li>Inscrição e agendamento do exame junto à Capitania</li>
<li>Acompanhamento do processo até a emissão da carteira</li>
<li>Renovação e 2ª via da habilitação</li>
</ul>
<h2>Documentos</h2>
<p>Em geral, são pedidos documento de identidade com foto, CPF e comprovante de residência. A lista exata depende da categoria e do tipo de pedido (primeira habilitação, renovação ou 2ª via). Conferimos tudo com você antes de dar entrada, para evitar exigências e retrabalho.</p>
<h2>Simulado online para alunos</h2>
<p>Nossos alunos têm acesso à Área do Cliente, onde treinam com questões no formato da prova da Marinha. É a melhor forma de chegar no dia do exame sabendo exatamente o que esperar.</p>
HTML,
                'faq' => [
                    ['pergunta' => 'Preciso de habilitação para pilotar jet ski?', 'resposta' => 'Sim. Para conduzir moto aquática é necessária a habilitação na categoria Motonauta, emitida pela Marinha do Brasil.'],
                    ['pergunta' => 'Moro no interior de Goiás. Vocês me atendem?', 'resposta' => 'Sim. Atendemos alunos de todo o estado de Goiás. O primeiro contato pode ser pelo WhatsApp, e explicamos como funciona cada etapa.'],
                    ['pergunta' => 'Minha habilitação venceu. O que faço?', 'resposta' => 'Cuidamos da renovação: conferimos os documentos e damos entrada no pedido junto à Marinha. Fale com a gente pelo WhatsApp.'],
                    ['pergunta' => 'Perdi minha carteira. Consigo a 2ª via?', 'resposta' => 'Sim. Fazemos o pedido de 2ª via da habilitação junto à Marinha.'],
                    ['pergunta' => 'Com Arrais Amador posso navegar no mar?', 'resposta' => 'O Arrais Amador é para navegação em águas interiores, como rios, lagos e represas. Para navegar no mar, a categoria é outra. Conte o seu caso e indicamos a habilitação adequada.'],
                ],
            ],

            'embarcacoes' => [
                'titulo_seo' => 'Inscrição e Renovação de Embarcação (TIE) em Goiânia',
                'meta_descricao' => 'Inscrição, renovação, 2ª via e alteração de dados de lanchas, jet skis, canoas e flutuantes junto à Marinha. Despachante náutico em Goiânia para todo o Brasil.',
                'conteudo' => <<<'HTML'
<h2>Documentação de embarcações junto à Marinha</h2>
<p>Toda embarcação sujeita a inscrição precisa estar registrada na Marinha do Brasil. O documento que comprova isso é o TIE (Título de Inscrição de Embarcação), que identifica a embarcação e o proprietário. Navegar com a documentação irregular pode gerar multa e outras penalidades na fiscalização.</p>
<p>A Campeão Náutica cuida desse processo para você, em Goiânia e em qualquer estado do Brasil.</p>
<h2>Serviços para a sua embarcação</h2>
<ul>
<li><strong>Inscrição</strong> de embarcação nova ou que nunca foi registrada</li>
<li><strong>Renovação</strong> do TIE</li>
<li><strong>2ª via</strong> do documento em caso de perda, roubo ou dano</li>
<li><strong>Alteração de dados</strong>, como nome da embarcação, motor e demais características</li>
</ul>
<p>Para compra e venda ou mudança de estado, veja também <a href="/servicos/transferencias-e-regularizacoes">transferências e regularizações</a>.</p>
<h2>Para quais embarcações</h2>
<p>Lanchas, barcos de passeio, motos aquáticas (jet ski), canoas, flutuantes e outras embarcações de esporte e recreio. Se não souber se a sua precisa de inscrição, pergunte: verificamos a exigência para o seu caso.</p>
<h2>Documentos</h2>
<p>Em geral, são pedidos os documentos pessoais do proprietário e a comprovação de propriedade da embarcação e do motor, como a nota fiscal. Dependendo do pedido, podem ser necessárias fotos e informações técnicas da embarcação. Passamos a lista completa no primeiro atendimento e conferimos tudo antes de dar entrada.</p>
<h2>Por que usar um despachante</h2>
<p>Um documento faltando ou preenchido de forma errada gera exigência e atrasa o processo. Com mais de 20 anos lidando com a Marinha, sabemos o que cada pedido precisa e acompanhamos até a liberação do documento.</p>
HTML,
                'faq' => [
                    ['pergunta' => 'O que é o TIE?', 'resposta' => 'É o Título de Inscrição de Embarcação, documento emitido pela Marinha que comprova a inscrição e identifica a embarcação e o proprietário.'],
                    ['pergunta' => 'Comprei uma lancha nova. Preciso inscrever?', 'resposta' => 'Sim, embarcações sujeitas a inscrição precisam ser registradas na Marinha. Cuidamos do processo a partir da nota fiscal e dos seus documentos.'],
                    ['pergunta' => 'Troquei o motor. Preciso atualizar o documento?', 'resposta' => 'Sim. A troca de motor deve ser atualizada no registro da embarcação. Fazemos o pedido de alteração de dados para você.'],
                    ['pergunta' => 'Minha embarcação fica em outro estado. Vocês atendem?', 'resposta' => 'Sim. Cuidamos da documentação de embarcações em qualquer estado do Brasil.'],
                ],
            ],

            'transferencias-e-regularizacoes' => [
                'titulo_seo' => 'Transferência de Embarcação e Jet Ski em Goiânia',
                'meta_descricao' => 'Transferência de propriedade e de jurisdição, atualização de documentos e regularização de embarcações e jet skis junto à Marinha. Atendemos todo o Brasil.',
                'conteudo' => <<<'HTML'
<h2>Comprou ou vendeu uma embarcação?</h2>
<p>Toda compra e venda de embarcação precisa ser registrada na Marinha do Brasil. Enquanto a transferência não é feita, a embarcação continua no nome do antigo proprietário, o que traz risco para quem vendeu e impede quem comprou de manter a documentação em dia.</p>
<p>A Campeão Náutica cuida da transferência do começo ao fim, para lanchas, barcos, jet skis e demais embarcações, em qualquer estado do Brasil.</p>
<h2>O que fazemos</h2>
<ul>
<li><strong>Transferência de propriedade</strong>: registro da embarcação no nome do novo dono</li>
<li><strong>Transferência de jurisdição</strong>: quando a embarcação passa para a área de outra Capitania</li>
<li><strong>Atualização de documentos</strong> e dados cadastrais</li>
<li><strong>Regularização</strong> de embarcações com documentação vencida, dados desatualizados ou pendências</li>
</ul>
<h2>Documentos</h2>
<p>Em geral, são pedidos o documento da embarcação (TIE), o documento de compra e venda com as assinaturas reconhecidas e os documentos pessoais do comprador e do vendedor. Cada caso tem suas particularidades. Analisamos a sua situação e passamos a lista completa antes de dar entrada.</p>
<h2>Embarcação com documentação atrasada</h2>
<p>É comum comprar uma embarcação usada e descobrir que os documentos estão vencidos ou desatualizados. Na maioria dos casos, há solução: analisamos a situação junto à Marinha e indicamos o caminho para regularizar.</p>
<h2>Dica para quem está vendendo</h2>
<p>Não entregue a embarcação sem formalizar a venda. Registrar a transferência protege você de responsabilidades por algo que aconteça com a embarcação depois da venda.</p>
HTML,
                'faq' => [
                    ['pergunta' => 'Vendi minha embarcação. Preciso fazer alguma coisa?', 'resposta' => 'Sim. Enquanto a transferência não é registrada na Marinha, a embarcação continua vinculada ao seu nome. Cuidamos do processo para você.'],
                    ['pergunta' => 'Comprei uma embarcação com documentos atrasados. Tem solução?', 'resposta' => 'Na maioria dos casos, sim. Analisamos a situação da embarcação e indicamos o que é preciso para regularizar.'],
                    ['pergunta' => 'A embarcação é de outro estado. Consigo transferir?', 'resposta' => 'Sim. Cuidamos da transferência de propriedade e também da transferência de jurisdição, quando a embarcação muda de área de Capitania.'],
                    ['pergunta' => 'Quais documentos preciso para a transferência?', 'resposta' => 'Em geral, o documento da embarcação, o documento de compra e venda com assinaturas reconhecidas e os documentos do comprador e do vendedor. Confirmamos a lista exata no atendimento.'],
                ],
            ],

            'despachante' => [
                'titulo_seo' => 'Despachante Náutico em Goiânia | Campeão Náutica',
                'meta_descricao' => 'Despachante náutico em Goiânia há mais de 20 anos: habilitação, documentação de embarcações, transferências e defesa de multas junto à Marinha do Brasil.',
                'conteudo' => <<<'HTML'
<h2>O que faz um despachante náutico</h2>
<p>O despachante náutico cuida dos processos junto à Marinha do Brasil para quem tem ou quer conduzir uma embarcação. Em vez de você descobrir sozinho quais documentos levar, preencher requerimentos e acompanhar cada etapa na Capitania, nós fazemos isso por você.</p>
<p>A Campeão Náutica atua em Goiânia há mais de 20 anos, com alunos de todo o estado de Goiás e regularização de embarcações em qualquer estado do Brasil.</p>
<h2>Serviços</h2>
<ul>
<li><a href="/servicos/habilitacao"><strong>Habilitação náutica</strong></a>: Arrais Amador e Motonauta, com emissão, renovação e 2ª via</li>
<li><a href="/servicos/embarcacoes"><strong>Documentação de embarcações</strong></a>: inscrição, renovação, 2ª via e alteração de dados</li>
<li><a href="/servicos/transferencias-e-regularizacoes"><strong>Transferências e regularizações</strong></a>: compra e venda, mudança de jurisdição e documentos atrasados</li>
<li><a href="/servicos/seguro-obrigatorio"><strong>Seguro obrigatório (DPEM)</strong></a>: orientação sobre a exigência para a sua embarcação</li>
<li><strong>Defesa de multas</strong>: análise do auto de infração e preparação da defesa junto à Marinha</li>
</ul>
<h2>Por que contratar</h2>
<ul>
<li><strong>Menos exigências</strong>: conferimos os documentos antes de dar entrada, para o pedido não voltar</li>
<li><strong>Menos deslocamento</strong>: você não precisa ir e voltar da Capitania a cada etapa</li>
<li><strong>Acompanhamento</strong>: avisamos em que ponto está o seu processo e quando o documento estiver liberado</li>
</ul>
<h2>Recebeu uma multa da Marinha?</h2>
<p>Se a sua embarcação foi autuada em uma fiscalização, existe prazo para apresentar defesa. Fale com a gente assim que receber a notificação: analisamos o auto de infração e preparamos a defesa.</p>
HTML,
                'faq' => [
                    ['pergunta' => 'O que faz um despachante náutico?', 'resposta' => 'Cuida dos processos junto à Marinha do Brasil, como habilitação, documentação de embarcações, transferências e defesa de multas, para que você não precise lidar com a burocracia.'],
                    ['pergunta' => 'Recebi uma multa da Marinha. Vocês fazem a defesa?', 'resposta' => 'Sim. Analisamos o auto de infração e preparamos a defesa. Procure a gente logo que receber a notificação, porque existe prazo para apresentar a defesa.'],
                    ['pergunta' => 'Preciso ir até o escritório?', 'resposta' => 'Não necessariamente. O atendimento começa pelo WhatsApp, e informamos se algum documento precisa ser entregue em mãos.'],
                    ['pergunta' => 'Vocês atendem fora de Goiás?', 'resposta' => 'Sim. Cuidamos da regularização de embarcações em qualquer estado do Brasil. A habilitação de Arrais Amador e Motonauta atende alunos de todo o estado de Goiás.'],
                ],
            ],

            'seguro-obrigatorio' => [
                'titulo_seo' => 'Seguro DPEM para Embarcações em Goiânia | Campeão Náutica',
                'meta_descricao' => 'Orientação sobre o seguro obrigatório DPEM para lanchas, jet skis e demais embarcações, e regularização da documentação junto à Marinha. Despachante em Goiânia.',
                'conteudo' => <<<'HTML'
<h2>O que é o seguro DPEM</h2>
<p>O DPEM (Seguro Obrigatório de Danos Pessoais Causados por Embarcações ou por suas Cargas) é o seguro obrigatório previsto em lei para embarcações. Ele indeniza pessoas, estejam elas a bordo ou não, em casos de morte, invalidez permanente e despesas médicas causadas por embarcações.</p>
<p>O DPEM não cobre danos à própria embarcação. Para isso, existe o seguro facultativo, contratado à parte.</p>
<h2>Quem precisa</h2>
<p>Proprietários de embarcações sujeitas a inscrição na Marinha, incluindo lanchas, barcos de passeio e motos aquáticas. As regras podem variar conforme o tipo de embarcação e mudar com o tempo, por isso verificamos a exigência em vigor para o seu caso.</p>
<h2>Como ajudamos</h2>
<ul>
<li>Orientação sobre a exigência do seguro para a sua embarcação</li>
<li>Conferência da documentação da embarcação</li>
<li>Regularização de pendências junto à Marinha</li>
</ul>
<h2>Documentação em dia</h2>
<p>O seguro é só uma parte. Aproveite o contato para conferir se o documento da embarcação (TIE) e a habilitação de quem conduz estão válidos. Se algo estiver pendente, cuidamos da <a href="/servicos/embarcacoes">documentação da embarcação</a> e da <a href="/servicos/habilitacao">habilitação náutica</a>.</p>
HTML,
                'faq' => [
                    ['pergunta' => 'O que o DPEM cobre?', 'resposta' => 'Indenizações por morte, invalidez permanente e despesas médicas de pessoas atingidas por embarcações, estejam elas a bordo ou não.'],
                    ['pergunta' => 'O DPEM cobre danos na minha lancha?', 'resposta' => 'Não. O DPEM cobre danos pessoais. Danos à embarcação dependem de um seguro facultativo, contratado à parte.'],
                    ['pergunta' => 'Jet ski precisa de seguro obrigatório?', 'resposta' => 'Motos aquáticas também são embarcações e seguem as regras de documentação da Marinha. Verificamos a exigência em vigor para o seu caso.'],
                ],
            ],

        ];
    }
}
