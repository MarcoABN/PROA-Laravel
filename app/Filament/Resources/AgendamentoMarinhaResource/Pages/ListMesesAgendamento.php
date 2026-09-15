<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Services\Agendamento\AlocaSolicitacao;
use App\Services\Agendamento\SemVagaException;
use App\Support\AcessoAgendamento;
use App\Support\EnderecoPublico;
use App\Support\ExtensaoChrome;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Tela de entrada: um card por mês com o andamento dos agendamentos.
 * "Cadastrar agendamentos" registra de uma vez os procuradores e clientes de um mês.
 */
class ListMesesAgendamento extends Page
{
    protected static string $resource = AgendamentoMarinhaResource::class;

    protected static string $view = 'filament.resources.agendamento-marinha.meses';

    protected static ?string $title = 'Agendamentos Marinha';

    // Página própria do Resource: o Filament não aplica o canAccess do Resource aqui sozinho.
    public static function canAccess(array $parameters = []): bool
    {
        return AcessoAgendamento::permitido();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getMesesProperty(): Collection
    {
        $servicos = DB::table('agendamento_solicitacoes')
            ->selectRaw('competencia, COUNT(*) AS total')
            ->groupBy('competencia')
            ->pluck('total', 'competencia');

        // Resumo por capitania dentro do mês: "CFGO: 4 agend. · CFMT: 1 agend."
        $porCapitania = DB::table('agendamentos_marinha')
            ->join('capitanias', 'capitanias.id', '=', 'agendamentos_marinha.capitania_id')
            ->where('agendamentos_marinha.status', '<>', AgendamentoMarinha::STATUS_CANCELADO)
            ->selectRaw('agendamentos_marinha.competencia, capitanias.sigla, COUNT(*) AS total')
            ->groupBy('agendamentos_marinha.competencia', 'capitanias.sigla')
            ->orderBy('capitanias.sigla')
            ->get()
            ->groupBy('competencia');

        return DB::table('agendamentos_marinha')
            ->selectRaw("
                competencia,
                COUNT(DISTINCT prestador_id) AS procuradores,
                COUNT(*) FILTER (WHERE status <> 'cancelado') AS agendamentos,
                COUNT(*) FILTER (WHERE status = 'agendado') AS agendados,
                COUNT(*) FILTER (WHERE status IN ('pendente', 'falhou')) AS pendentes,
                COUNT(*) FILTER (WHERE status = 'falhou') AS falhas
            ")
            ->groupBy('competencia')
            ->orderByDesc('competencia')
            ->get()
            ->map(function ($mes) use ($servicos, $porCapitania) {
                $data = Carbon::parse($mes->competencia);

                return [
                    'capitanias' => ($porCapitania[$mes->competencia] ?? collect())
                        ->map(fn($linha) => "{$linha->sigla}: {$linha->total} agend.")
                        ->implode(' · '),
                    'nome' => ucfirst($data->locale('pt_BR')->translatedFormat('F \d\e Y')),
                    'rotulo' => $data->format('m/Y'),
                    'url' => AgendamentoMarinhaResource::getUrl('mes', ['competencia' => $data->format('Y-m')]),
                    'servicos' => (int) ($servicos[$mes->competencia] ?? 0),
                    'procuradores' => (int) $mes->procuradores,
                    'agendamentos' => (int) $mes->agendamentos,
                    'agendados' => (int) $mes->agendados,
                    'pendentes' => (int) $mes->pendentes,
                    'falhas' => (int) $mes->falhas,
                ];
            });
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
                    . '<li>Clique no ícone da extensão e informe o endereço e o token (botão <strong>Token da extensão</strong>).</li>'
                    . '<li>Para atualizar: extraia o zip novo por cima da mesma pasta e clique em <strong>Recarregar (↻)</strong> na extensão.</li>'
                    . '</ol>'
                ))
                ->modalSubmitActionLabel(fn() => 'Baixar versão ' . (ExtensaoChrome::versaoAtual() ?? ''))
                ->action(fn() => response()
                    ->download(ExtensaoChrome::gerarZip(), ExtensaoChrome::nomeDoArquivo())
                    ->deleteFileAfterSend()),

            Actions\Action::make('tokenExtensao')
                ->label('Token da extensão')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Gerar seu token da extensão')
                ->modalDescription(function () {
                    $geradoEm = Auth::user()?->sisap_token_gerado_em;

                    return 'O token vale para qualquer procurador: a extensão carrega os agendamentos do CPF logado no SISAP. '
                        . ($geradoEm ? "Seu token atual (gerado em {$geradoEm->format('d/m/Y H:i')}) deixará de funcionar." : '');
                })
                ->action(function () {
                    $token = Auth::user()->gerarTokenSisap();

                    Notification::make()
                        ->title('Token gerado — copie agora')
                        ->body(EnderecoPublico::instrucoesExtensao($token))
                        ->persistent()
                        ->success()
                        ->send();
                }),

            Actions\Action::make('cadastrar')
                ->label('Cadastrar agendamentos')
                ->icon('heroicon-o-plus')
                ->modalHeading('Cadastrar agendamentos do mês')
                ->modalDescription('Informe o mês e, para cada procurador, os clientes com GRU e serviço. O PROA divide os serviços entre os agendamentos do procurador.')
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->modalSubmitActionLabel('Cadastrar')
                ->form([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('competencia')
                            ->label('Mês do atendimento')
                            ->options(AgendamentoMarinha::competenciasDisponiveis())
                            ->default(fn() => array_keys(AgendamentoMarinha::competenciasDisponiveis())[1])
                            ->required()
                            ->live()
                            ->native(false)
                            ->helperText('Pode repetir o cadastro no mesmo mês para outras capitanias ou procuradores.'),
                    ]),

                    // Um item por procurador + capitania: o mesmo procurador pode aparecer de novo em outra capitania.
                    Forms\Components\Repeater::make('procuradores')
                        ->label('Procuradores')
                        ->addActionLabel('Adicionar procurador')
                        ->defaultItems(1)
                        ->minItems(1)
                        ->reorderable(false)
                        ->collapsible()
                        ->columns(4)
                        ->itemLabel(fn(array $state) => collect([
                            AgendamentoMarinhaResource::opcoesProcuradores()[$state['prestador_id'] ?? null] ?? 'Procurador',
                            Capitania::find($state['capitania_id'] ?? null)?->sigla,
                        ])->filter()->implode(' · '))
                        ->schema([
                            Forms\Components\Select::make('prestador_id')
                                ->label('Procurador')
                                ->options(fn() => AgendamentoMarinhaResource::opcoesProcuradores())
                                ->searchable()
                                ->required()
                                ->live()
                                ->helperText(fn(Get $get) => AgendamentoMarinhaResource::textoVagas(
                                    $get('prestador_id'), $get('capitania_id'), $get('../../competencia'),
                                )),

                            Forms\Components\Select::make('capitania_id')
                                ->label('Capitania')
                                ->options(fn() => AgendamentoMarinhaResource::opcoesCapitanias())
                                ->default(fn() => Capitania::where('padrao', true)->value('id'))
                                ->required()
                                ->live()
                                ->native(false),

                            AgendamentoMarinhaResource::campoDataSugerida('../../competencia'),

                            AgendamentoMarinhaResource::campoPeriodo(),

                            Forms\Components\Repeater::make('clientes')
                                ->label('Clientes')
                                ->columnSpanFull()
                                ->addActionLabel('Adicionar cliente')
                                ->defaultItems(1)
                                ->minItems(1)
                                ->reorderable(false)
                                ->columns(4)
                                ->schema([
                                    AgendamentoMarinhaResource::campoCpf(),
                                    AgendamentoMarinhaResource::campoNome(),
                                    AgendamentoMarinhaResource::campoGru()->unique('agendamento_solicitacoes', 'gru'),
                                    AgendamentoMarinhaResource::campoServico(),
                                ]),
                        ]),
                ])
                ->action(function (array $data, Actions\Action $action) {
                    try {
                        $mes = AlocaSolicitacao::cadastrarLote($data);
                    } catch (SemVagaException | InvalidArgumentException $e) {
                        Notification::make()->title('Nada foi cadastrado')->body($e->getMessage())->danger()->send();
                        $action->halt();

                        return;
                    }

                    Notification::make()->title('Agendamentos cadastrados')->success()->send();

                    $this->redirect(AgendamentoMarinhaResource::getUrl('mes', ['competencia' => $mes]));
                }),
        ];
    }
}
