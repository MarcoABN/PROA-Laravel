<?php

namespace Database\Seeders;

use App\Models\Artigo;
use App\Models\Servico;
use Illuminate\Database\Seeder;

/**
 * Página "Arrais Amador" (/servicos/arrais-amador), artigo "Como tirar Arrais
 * Amador em Goiânia" (/blog/...) e reposicionamento da página "Habilitação"
 * para emissão, renovação e 2ª via, evitando que as duas páginas disputem as
 * mesmas buscas.
 *
 * Não sobrescreve nada editado no PROA: só cria o que não existe, preenche
 * campos vazios e troca o título/descrição de "Habilitação" apenas se ainda
 * estiverem com o texto original do ServicosConteudoSeeder.
 *
 * php artisan db:seed --class=ArraisAmadorSeeder --force
 */
class ArraisAmadorSeeder extends Seeder
{
    private const HABILITACAO_TITULO_ANTIGO = 'Arrais Amador e Motonauta em Goiânia | Habilitação Náutica';
    private const HABILITACAO_META_ANTIGA = 'Tire, renove ou peça a 2ª via da sua habilitação náutica de Arrais Amador ou Motonauta. Escola náutica em Goiânia com alunos de todo o estado de Goiás.';

    public function run(): void
    {
        $this->criarServicoArrais();
        $this->reposicionarHabilitacao();
        $this->criarArtigo();
    }

    private function criarServicoArrais(): void
    {
        $dados = [
            'nome' => 'Arrais Amador',
            'descricao' => 'Preparação para a prova, simulado online e toda a documentação para você tirar a habilitação de Arrais Amador em Goiânia.',
            'titulo_seo' => 'Arrais Amador em Goiânia: Curso e Habilitação | Campeão Náutica',
            'meta_descricao' => 'Tire sua habilitação de Arrais Amador em Goiânia com a Campeão Náutica: preparação para a prova, simulado online e toda a documentação junto à Marinha.',
            'conteudo' => <<<'HTML'
<h2>Onde tirar Arrais Amador em Goiânia</h2>
<p>A Campeão Náutica é escola náutica e despachante em Goiânia há mais de 20 anos. Preparamos você para a prova da Marinha do Brasil e cuidamos de toda a documentação da habilitação de Arrais Amador, da inscrição até a entrega da carteira.</p>
<p>Atendemos alunos de Goiânia e de todo o estado de Goiás. O escritório fica na Avenida 24 de Outubro, 3053, no Aeroviário, e o primeiro contato pode ser pelo WhatsApp.</p>
<h2>O que é o Arrais Amador</h2>
<p>Arrais Amador é a categoria da Carteira de Habilitação de Amador (CHA) que permite conduzir embarcações de esporte e recreio, como lanchas e barcos de passeio, em águas interiores: rios, lagos e represas. É a habilitação de quem vai navegar nos lagos e reservatórios de Goiás.</p>
<p>Para pilotar jet ski, a categoria é outra, a de <a href="/servicos/habilitacao">Motonauta</a>. Quem vai usar lancha e moto aquática pode ter as duas.</p>
<h2>Como funciona com a Campeão Náutica</h2>
<ol>
<li><strong>Conversa inicial</strong>: você conta como pretende navegar e confirmamos a categoria certa.</li>
<li><strong>Documentos</strong>: passamos a lista e conferimos tudo antes de dar entrada.</li>
<li><strong>Preparação</strong>: você estuda e treina no nosso simulado online, com questões no formato da prova.</li>
<li><strong>Inscrição e prova</strong>: cuidamos da inscrição e do agendamento do exame junto à Marinha.</li>
<li><strong>Carteira</strong>: acompanhamos o processo e avisamos quando a habilitação estiver liberada.</li>
</ol>
<h2>O que estudar para a prova</h2>
<p>A prova é teórica e cobre os conhecimentos básicos para navegar com segurança, como regras de navegação e de prevenção de colisões, sinalização náutica, legislação da Marinha e noções de segurança a bordo. No simulado, você treina com questões nesse formato e chega ao exame sabendo o que esperar.</p>
<h2>Documentos</h2>
<p>Em geral, são pedidos documento de identidade com foto, CPF e comprovante de residência. Confirmamos a lista exata no atendimento, conforme o seu caso.</p>
<p>Quer entender todas as etapas antes de começar? Leia o nosso guia <a href="/blog/como-tirar-arrais-amador-em-goiania">como tirar Arrais Amador em Goiânia</a>.</p>
HTML,
            'faq' => [
                ['pergunta' => 'Onde tirar Arrais Amador em Goiânia?', 'resposta' => 'Na Campeão Náutica, na Avenida 24 de Outubro, 3053, Aeroviário. Preparamos você para a prova e cuidamos de toda a documentação junto à Marinha. Atendemos alunos de todo o estado de Goiás.'],
                ['pergunta' => 'Arrais Amador serve para pilotar jet ski?', 'resposta' => 'Não. Para moto aquática a categoria é Motonauta. Quem vai usar lancha e jet ski pode ter as duas habilitações.'],
                ['pergunta' => 'Como é a prova de Arrais Amador?', 'resposta' => 'É uma prova teórica aplicada pela Marinha, com questões sobre regras de navegação, sinalização náutica, legislação e segurança. Nossos alunos treinam antes no simulado online.'],
                ['pergunta' => 'Com Arrais Amador posso navegar em lagos e represas de Goiás?', 'resposta' => 'Sim. O Arrais Amador habilita a conduzir embarcações de esporte e recreio em águas interiores, como rios, lagos e represas.'],
                ['pergunta' => 'Moro no interior de Goiás. Posso tirar com vocês?', 'resposta' => 'Sim. Atendemos alunos de todo o estado. O atendimento começa pelo WhatsApp, e explicamos cada etapa.'],
            ],
        ];

        $servico = Servico::firstOrNew(['slug' => 'arrais-amador']);
        $novo = ! $servico->exists;

        foreach ($dados as $campo => $valor) {
            if (blank($servico->{$campo})) {
                $servico->{$campo} = $valor;
            }
        }

        if ($novo) {
            $servico->ativo = true;
        }

        $this->salvar($servico, 'Serviço arrais-amador', $novo);
    }

    private function reposicionarHabilitacao(): void
    {
        $servico = Servico::where('slug', 'habilitacao')->first();

        if (! $servico) {
            $this->command?->warn('Serviço não encontrado: habilitacao');
            return;
        }

        if (blank($servico->titulo_seo) || $servico->titulo_seo === self::HABILITACAO_TITULO_ANTIGO) {
            $servico->titulo_seo = 'Habilitação Náutica em Goiânia: Emissão, Renovação e 2ª Via';
        }

        if (blank($servico->meta_descricao) || $servico->meta_descricao === self::HABILITACAO_META_ANTIGA) {
            $servico->meta_descricao = 'Emissão, renovação e 2ª via da habilitação náutica (CHA) de Arrais Amador e Motonauta. Escola náutica em Goiânia com alunos de todo o estado de Goiás.';
        }

        if (filled($servico->conteudo) && ! str_contains($servico->conteudo, '/servicos/arrais-amador')) {
            $servico->conteudo .= "\n" . '<p>Vai tirar a sua primeira habilitação para lancha ou barco? Veja como funciona o <a href="/servicos/arrais-amador">Arrais Amador em Goiânia</a>.</p>';
        }

        $this->salvar($servico, 'Serviço habilitacao');
    }

    private function criarArtigo(): void
    {
        $artigo = Artigo::firstOrNew(['slug' => 'como-tirar-arrais-amador-em-goiania']);

        if ($artigo->exists) {
            $this->command?->line('Sem alterações: artigo já existe');
            return;
        }

        $artigo->fill([
            'titulo' => 'Como tirar Arrais Amador em Goiânia: passo a passo',
            'resumo' => 'Da escolha da categoria à carteira na mão: veja as etapas para tirar a habilitação de Arrais Amador em Goiânia e o que fazer em cada uma.',
            'titulo_seo' => 'Como tirar Arrais Amador em Goiânia: passo a passo | Campeão Náutica',
            'meta_descricao' => 'Veja o passo a passo para tirar Arrais Amador em Goiânia: categoria certa, documentos, estudo, prova da Marinha e emissão da habilitação.',
            'ativo' => true,
            'publicado_em' => now(),
            'conteudo' => <<<'HTML'
<p>Quem compra uma lancha ou pretende pilotar o barco da família nos lagos e represas de Goiás precisa, antes de tudo, da habilitação da Marinha do Brasil. Para esse tipo de embarcação, a categoria é o <strong>Arrais Amador</strong>. Neste guia, explicamos cada etapa para tirar a sua em Goiânia.</p>
<h2>1. Confirme se Arrais Amador é a categoria certa</h2>
<p>A Carteira de Habilitação de Amador (CHA) tem categorias diferentes conforme o tipo de embarcação e o local onde você vai navegar:</p>
<ul>
<li><strong>Arrais Amador</strong>: embarcações de esporte e recreio, como lanchas e barcos, em águas interiores (rios, lagos e represas).</li>
<li><strong>Motonauta</strong>: motos aquáticas, como o jet ski.</li>
<li><strong>Categorias para o mar</strong>: quem pretende navegar no litoral precisa de uma categoria superior.</li>
</ul>
<p>Para a maioria de quem navega em Goiás, o Arrais Amador resolve. Se você também vai pilotar jet ski, vale tirar o Motonauta junto.</p>
<h2>2. Separe os documentos</h2>
<p>Em geral, são pedidos documento de identidade com foto, CPF e comprovante de residência. A lista pode variar conforme o caso, então confirme antes de dar entrada. Um documento faltando ou desatualizado gera exigência e faz o processo voltar.</p>
<h2>3. Estude para a prova</h2>
<p>A prova de Arrais Amador é teórica e cobre o que todo condutor precisa saber para navegar com segurança. Os principais assuntos são:</p>
<ul>
<li>Regras de navegação e de prevenção de colisões, como quem tem preferência quando duas embarcações se cruzam</li>
<li>Sinalização náutica, as boias e sinais que indicam por onde navegar</li>
<li>Legislação e normas da Marinha para embarcações de esporte e recreio</li>
<li>Segurança a bordo e equipamentos obrigatórios</li>
</ul>
<p>A melhor forma de se preparar é resolver questões no mesmo formato da prova. Por isso, nossos alunos treinam no simulado online da Área do Cliente antes do exame.</p>
<h2>4. Faça a inscrição e agende a prova</h2>
<p>A inscrição e o agendamento do exame são feitos junto à Marinha. É aqui que muita gente se perde: formulários, taxas e agendamento têm regras próprias. Uma escola náutica ou despachante cuida dessa parte e confere tudo antes do envio.</p>
<h2>5. Faça a prova</h2>
<p>No dia marcado, leve os documentos pedidos e chegue com antecedência. Quem treinou com simulados costuma chegar mais tranquilo, porque já conhece o estilo das questões.</p>
<h2>6. Receba a habilitação</h2>
<p>Depois da aprovação, a Marinha emite a sua CHA na categoria Arrais Amador. A partir daí, você pode conduzir embarcações de esporte e recreio em águas interiores. Lembre-se de manter também a documentação da embarcação em dia.</p>
<h2>Onde tirar Arrais Amador em Goiânia</h2>
<p>A Campeão Náutica é escola náutica e despachante em Goiânia há mais de 20 anos. Cuidamos de todas as etapas acima: orientação, documentos, preparação com simulado, inscrição e acompanhamento até a entrega da carteira. Atendemos alunos de todo o estado de Goiás.</p>
<p>Saiba mais na página do <a href="/servicos/arrais-amador">Arrais Amador em Goiânia</a> ou fale com a gente pelo WhatsApp.</p>
HTML,
            'faq' => [
                ['pergunta' => 'Preciso de habilitação para pilotar lancha em represa?', 'resposta' => 'Sim. Para conduzir embarcações de esporte e recreio em rios, lagos e represas, a Marinha exige a habilitação na categoria Arrais Amador.'],
                ['pergunta' => 'A prova de Arrais Amador é difícil?', 'resposta' => 'É uma prova teórica sobre regras de navegação, sinalização, legislação e segurança. Com estudo e treino em simulados no formato da prova, a maioria dos alunos chega bem preparada.'],
                ['pergunta' => 'Posso tirar Arrais Amador e Motonauta ao mesmo tempo?', 'resposta' => 'Sim. Quem vai usar lancha e jet ski pode ter as duas categorias. Confirmamos no atendimento o que é preciso para cada uma.'],
                ['pergunta' => 'Onde fazer a prova de Arrais em Goiânia?', 'resposta' => 'A prova é aplicada pela Marinha. Na Campeão Náutica, cuidamos da inscrição e do agendamento e informamos local e horário do seu exame.'],
            ],
        ]);

        $this->salvar($artigo, 'Artigo como-tirar-arrais-amador-em-goiania', true);
    }

    private function salvar($modelo, string $nome, bool $novo = false): void
    {
        if (! $modelo->isDirty()) {
            $this->command?->line("Sem alterações: {$nome}");
            return;
        }

        $campos = implode(', ', array_keys($modelo->getDirty()));
        $modelo->save();
        $this->command?->info(($novo ? 'Criado: ' : 'Atualizado: ') . "{$nome} ({$campos})");
    }
}
