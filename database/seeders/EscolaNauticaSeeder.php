<?php

namespace Database\Seeders;

use App\Models\Servico;
use Illuminate\Database\Seeder;

/**
 * Página "Escola Náutica" (/servicos/escola-nautica), para as buscas por
 * "escola náutica em Goiânia" e afins, em que o site não aparecia por não ter
 * nenhuma página dedicada ao termo.
 *
 * Só cria o que não existe e preenche campos vazios.
 *
 * php artisan db:seed --class=EscolaNauticaSeeder --force
 */
class EscolaNauticaSeeder extends Seeder
{
    public function run(): void
    {
        $dados = [
            'nome' => 'Escola Náutica',
            'descricao' => 'Escola náutica autorizada pela Marinha do Brasil: preparação para as provas de Arrais Amador e Motonauta, com simulado online e documentação incluída.',
            'titulo_seo' => 'Escola Náutica em Goiânia: Arrais Amador e Motonauta | Campeão',
            'meta_descricao' => 'Escola náutica autorizada pela Marinha do Brasil em Goiânia. Preparação para Arrais Amador e Motonauta, simulado online e documentação. Alunos de todo Goiás.',
            'conteudo' => <<<'HTML'
<h2>Escola náutica em Goiânia autorizada pela Marinha do Brasil</h2>
<p>A Campeão Náutica é escola náutica e assessoria naval em Goiânia, autorizada pela Marinha do Brasil e atuando há mais de 20 anos. Preparamos alunos para as provas de habilitação e cuidamos de toda a documentação, da inscrição até a entrega da carteira.</p>
<p>Atendemos alunos de Goiânia e de todo o estado de Goiás, no escritório da Avenida 24 de Outubro, 3053, no Aeroviário.</p>
<h2>Habilitações que preparamos</h2>
<ul>
<li><a href="/servicos/arrais-amador"><strong>Arrais Amador</strong></a>: para conduzir lanchas e barcos de esporte e recreio em águas interiores, como rios, lagos e represas.</li>
<li><strong>Motonauta</strong>: para conduzir motos aquáticas, como o jet ski.</li>
</ul>
<p>Quem vai usar lancha e jet ski pode tirar as duas categorias. Conte como pretende navegar e indicamos a habilitação certa.</p>
<h2>Como preparamos você</h2>
<ol>
<li><strong>Orientação inicial</strong>: explicamos a categoria, as etapas e os documentos necessários.</li>
<li><strong>Estudo</strong>: você aprende os assuntos cobrados na prova, como regras de navegação e de prevenção de colisões, sinalização náutica, legislação e segurança a bordo.</li>
<li><strong>Simulado online</strong>: nossos alunos treinam na Área do Cliente com questões no formato do exame da Marinha.</li>
<li><strong>Inscrição e prova</strong>: cuidamos da inscrição e do agendamento do exame.</li>
<li><strong>Carteira</strong>: acompanhamos o processo e avisamos quando a habilitação estiver liberada.</li>
</ol>
<h2>Escola e despachante no mesmo lugar</h2>
<p>Esta é a diferença de estudar com a gente: o mesmo time que prepara você para a prova cuida da <a href="/servicos/habilitacao">habilitação</a>, da <a href="/servicos/embarcacoes">documentação da embarcação</a> e das <a href="/servicos/transferencias-e-regularizacoes">transferências e regularizações</a>. Você resolve tudo num contato só, sem precisar procurar um despachante depois de passar na prova.</p>
<h2>Para quem é</h2>
<p>Para quem comprou ou pretende comprar uma lancha ou um jet ski, para quem pesca nos lagos e represas de Goiás e para quem quer navegar com a família dentro da lei. Não é preciso ter experiência: começamos do zero com você.</p>
<p>Quer entender as etapas antes de decidir? Leia o guia <a href="/blog/como-tirar-arrais-amador-em-goiania">como tirar Arrais Amador em Goiânia</a>.</p>
HTML,
            'faq' => [
                ['pergunta' => 'A Campeão Náutica é uma escola náutica autorizada pela Marinha?', 'resposta' => 'Sim. Somos escola náutica autorizada pela Marinha do Brasil e atuamos em Goiânia há mais de 20 anos.'],
                ['pergunta' => 'Quais habilitações vocês preparam?', 'resposta' => 'Arrais Amador, para lanchas e barcos em águas interiores, e Motonauta, para motos aquáticas. Quem usa os dois tipos de embarcação pode tirar as duas categorias.'],
                ['pergunta' => 'Preciso ter experiência para começar?', 'resposta' => 'Não. A maioria dos nossos alunos nunca navegou antes. Explicamos tudo desde o início e você treina no simulado online antes da prova.'],
                ['pergunta' => 'Vocês atendem alunos do interior de Goiás?', 'resposta' => 'Sim. Atendemos alunos de todo o estado de Goiás. O primeiro contato pode ser pelo WhatsApp.'],
                ['pergunta' => 'A escola também cuida da documentação?', 'resposta' => 'Sim. Além de escola náutica, somos despachante náutico: cuidamos da habilitação, da documentação da embarcação, de transferências e de regularizações junto à Marinha.'],
            ],
        ];

        $servico = Servico::firstOrNew(['slug' => 'escola-nautica']);
        $novo = ! $servico->exists;

        foreach ($dados as $campo => $valor) {
            if (blank($servico->{$campo})) {
                $servico->{$campo} = $valor;
            }
        }

        if ($novo) {
            $servico->ativo = true;
        }

        if (! $servico->isDirty()) {
            $this->command?->line('Sem alterações: escola-nautica');
            return;
        }

        $campos = implode(', ', array_keys($servico->getDirty()));
        $servico->save();
        $this->command?->info(($novo ? 'Criado' : 'Atualizado') . ": escola-nautica ({$campos})");
    }
}
