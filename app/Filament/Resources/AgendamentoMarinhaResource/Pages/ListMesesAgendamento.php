<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use App\Models\AgendamentoMarinha;
use App\Services\Agendamento\VisibilidadeNaExtensao;
use App\Support\AcessoAgendamento;
use App\Support\AcoesTokenSisap;
use App\Support\ExtensaoChrome;
use App\Support\Feriados;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;

/**
 * Tela de entrada: o calendário do ano, com os dias que têm agendamento marcado no SISAP.
 * Clicar num mês abre o mês (os balões de capitania + procurador).
 */
class ListMesesAgendamento extends Page
{
    protected static string $resource = AgendamentoMarinhaResource::class;

    protected static string $view = 'filament.resources.agendamento-marinha.meses';

    protected static ?string $title = 'Agendamentos Marinha';

    #[Url]
    public ?int $ano = null;

    // Página própria do Resource: o Filament não aplica o canAccess do Resource aqui sozinho.
    public static function canAccess(array $parameters = []): bool
    {
        return AcessoAgendamento::permitido();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->ano = $this->ano >= 2000 && $this->ano <= 2100 ? $this->ano : (int) now()->year;
    }

    /** É a primeira tela: sem a trilha "Agendamentos Marinha > Agendamentos Marinha". */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function anoAnterior(): void
    {
        $this->ano--;
    }

    public function proximoAno(): void
    {
        $this->ano++;
    }

    /**
     * Os 12 meses do ano: semanas (domingo a sábado) com os dias, os agendamentos marcados em cada dia
     * e quantos agendamentos do mês estão marcados, aguardando ou com falha.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMesesProperty(): array
    {
        $marcados = AgendamentoMarinha::query()
            ->with(['prestador', 'capitania'])
            ->where('status', AgendamentoMarinha::STATUS_AGENDADO)
            ->whereYear('data_hora', $this->ano)
            ->orderBy('data_hora')
            ->get()
            ->groupBy(fn(AgendamentoMarinha $a) => $a->data_hora->toDateString())
            ->map(fn(Collection $doDia) => $doDia
                ->map(fn(AgendamentoMarinha $a) => $a->data_hora->format('H:i') . " · {$a->capitania?->sigla} · {$a->prestador?->nome}")
                ->all());

        $situacao = DB::table('agendamentos_marinha')
            ->selectRaw("
                competencia,
                COUNT(*) FILTER (WHERE status = 'agendado') AS agendados,
                COUNT(*) FILTER (WHERE status = 'pendente') AS aguardando,
                COUNT(*) FILTER (WHERE status = 'falhou') AS falhas
            ")
            ->whereYear('competencia', $this->ano)
            ->groupBy('competencia')
            ->get()
            ->keyBy(fn($linha) => Carbon::parse($linha->competencia)->month);

        $feriados = Feriados::doAno($this->ano);
        $hoje = now()->toDateString();

        return collect(range(1, 12))->map(function (int $mes) use ($marcados, $situacao, $feriados, $hoje) {
            $inicio = Carbon::create($this->ano, $mes, 1);
            $dias = array_fill(0, $inicio->dayOfWeek, null); // casas vazias até o dia 1 (domingo = 0)

            for ($dia = $inicio->copy(); $dia->month === $mes; $dia->addDay()) {
                $data = $dia->toDateString();

                $dias[] = [
                    'dia' => $dia->day,
                    'marcados' => $marcados[$data] ?? [],
                    'folga' => $dia->isWeekend() || isset($feriados[$data]),
                    'feriado' => $feriados[$data] ?? null,
                    'hoje' => $data === $hoje,
                ];
            }

            $linha = $situacao[$mes] ?? null;

            return [
                'nome' => ucfirst($inicio->locale('pt_BR')->translatedFormat('F')),
                'url' => AgendamentoMarinhaResource::getUrl('mes', ['competencia' => $inicio->format('Y-m')]),
                'atual' => $inicio->isSameMonth(now()),
                'naExtensao' => VisibilidadeNaExtensao::motivo($inicio) !== null,
                'semanas' => array_chunk(array_pad($dias, (int) ceil(count($dias) / 7) * 7, null), 7),
                'agendados' => (int) ($linha->agendados ?? 0),
                'aguardando' => (int) ($linha->aguardando ?? 0),
                'falhas' => (int) ($linha->falhas ?? 0),
            ];
        })->all();
    }

    /** Aviso no topo da tela para quem usa uma versão antiga da extensão com o próprio token. */
    public function getAlertaExtensaoProperty(): ?string
    {
        return ExtensaoChrome::aviso(Auth::user()?->sisap_extensao_versao);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('baixarExtensao')
                ->label('Baixar extensão')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading('Instalar a extensão do Chrome')
                ->modalDescription(new HtmlString(
                    '<ol style="list-style: decimal; padding-left: 1.25rem; text-align: left; line-height: 1.6;">'
                    . '<li>Baixe o zip e extraia numa pasta fixa, por exemplo <code>C:\PROA</code> (não apague nem mova depois).</li>'
                    . '<li>No Chrome, abra <code>chrome://extensions</code> e ligue o <strong>Modo do desenvolvedor</strong>.</li>'
                    . '<li>Clique em <strong>Carregar sem compactação</strong> e escolha a pasta <code>proa-agendamento-marinha</code>.</li>'
                    . '<li>Clique no ícone da extensão e informe o endereço e o token (<strong>Tokens da extensão → Gerar token</strong>, um para cada navegador).</li>'
                    . '<li>Para atualizar: extraia o zip novo por cima da mesma pasta e clique em <strong>Recarregar (↻)</strong> na extensão.</li>'
                    . '</ol>'
                ))
                ->modalSubmitActionLabel(fn() => 'Baixar versão ' . (ExtensaoChrome::versaoAtual() ?? ''))
                ->action(fn() => response()
                    ->download(ExtensaoChrome::gerarZip(), ExtensaoChrome::nomeDoArquivo())
                    ->deleteFileAfterSend()),

            Actions\ActionGroup::make([
                AcoesTokenSisap::gerar(
                    Actions\Action::make('gerarTokenExtensao'),
                    fn() => Auth::user(),
                    'O token vale para qualquer procurador: a extensão carrega os agendamentos do CPF logado no SISAP.',
                ),
                AcoesTokenSisap::revogar(Actions\Action::make('revogarTokensExtensao'), fn() => Auth::user()),
            ])
                ->label('Tokens da extensão')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->button(),
        ];
    }
}
