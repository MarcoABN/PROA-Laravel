<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Models\Prestador;
use App\Models\SolicitacaoAgendamento;
use App\Services\Agendamento\CadastroDoMes;
use App\Support\AcessoAgendamento;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Livewire\Attributes\Locked;

/**
 * Um "balão" do mês: os agendamentos de um procurador numa capitania.
 *
 * Abre com todos os agendamentos que a capitania permite e, em cada um, todas as linhas de cliente
 * (vagas por agendamento): o usuário só preenche. Linha deixada em branco não é cliente.
 * Agendamentos já marcados no SISAP aparecem só para consulta.
 */
class CadastroAgendamentos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AgendamentoMarinhaResource::class;

    protected static string $view = 'filament.resources.agendamento-marinha.cadastro';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** Chave do balão. Locked: o navegador não consegue trocá-la e salvar em outro lugar. */
    #[Locked]
    public string $competencia = '';

    #[Locked]
    public int $capitaniaId = 0;

    #[Locked]
    public int $prestadorId = 0;

    /** @var array<int, AgendamentoMarinha|null> */
    private array $agendamentos = [];

    public static function canAccess(array $parameters = []): bool
    {
        return AcessoAgendamento::permitido();
    }

    public function mount(string $competencia, string $capitania, string $procurador): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless((bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $competencia), 404);

        $capitaniaModel = Capitania::find($capitania);
        $prestador = Prestador::where('is_procurador', true)->find($procurador);
        abort_unless($capitaniaModel && $prestador, 404);

        $this->competencia = "{$competencia}-01";
        $this->capitaniaId = $capitaniaModel->id;
        $this->prestadorId = $prestador->id;

        $bloco = CadastroDoMes::carregarProcurador($this->capitaniaId, $this->competencia, $this->prestadorId);

        // Todas as linhas de cliente já abertas nos agendamentos que ainda podem ser alterados.
        foreach ($bloco as $chave => $slot) {
            if (str_starts_with($chave, 'agendamento_') && !$this->marcado($slot['id'])) {
                $bloco[$chave]['clientes'] = array_pad($slot['clientes'], $this->vagasPorAgendamento(), static::linhaVazia());
            }
        }

        // Os agendamentos do formulário saem dos dados: o Filament montou o form antes deles existirem.
        $this->data = $bloco;
        $this->cacheForms();
        $this->form->fill($bloco);
    }

    public function capitania(): Capitania
    {
        return Capitania::findOrFail($this->capitaniaId);
    }

    public function getTitle(): string
    {
        return Prestador::find($this->prestadorId)?->nome . ' · ' . $this->capitania()->sigla;
    }

    public function getSubheading(): string
    {
        return $this->capitania()->nome . ' · ' . ucfirst(Carbon::parse($this->competencia)->locale('pt_BR')->translatedFormat('F \d\e Y'));
    }

    public function getBreadcrumbs(): array
    {
        $mes = Carbon::parse($this->competencia);

        return [
            AgendamentoMarinhaResource::getUrl() => 'Agendamentos Marinha',
            AgendamentoMarinhaResource::getUrl('mes', ['competencia' => $mes->format('Y-m')]) => $mes->format('m/Y'),
            $this->getTitle(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('voltar')
                ->label('Voltar ao mês')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AgendamentoMarinhaResource::getUrl('mes', ['competencia' => Carbon::parse($this->competencia)->format('Y-m')])),
        ];
    }

    public function form(Form $form): Form
    {
        $slots = collect(array_keys($this->data ?? []))
            ->filter(fn(string $chave) => str_starts_with($chave, 'agendamento_'))
            ->map(fn(string $chave) => (int) substr($chave, 12))
            ->sort()
            ->values();

        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Hidden::make('prestador_id'),

                Forms\Components\Section::make('Preferência de data')
                    ->description('Opcional. Se a data não tiver vaga, o PROA procura a data mais próxima com horário no período escolhido.')
                    ->compact()
                    ->columns(2)
                    ->schema([
                        $this->campoDataSugerida(),

                        Forms\Components\Select::make('periodo')
                            ->label('Período preferido')
                            ->options(AgendamentoMarinha::periodos())
                            ->placeholder('Tanto faz')
                            ->native(false),
                    ]),

                Forms\Components\Placeholder::make('semAgendamentos')
                    ->hiddenLabel()
                    ->content("{$this->capitania()->sigla} não permite agendamentos por procurador (veja o cadastro da capitania).")
                    ->visible($slots->isEmpty()),

                ...$slots->map(fn(int $ordem) => $this->secaoAgendamento($ordem))->all(),
            ]);
    }

    /** Calendário restrito ao mês do atendimento, com fins de semana e feriados em vermelho. */
    private function campoDataSugerida(): Forms\Components\DatePicker
    {
        $mes = Carbon::parse($this->competencia);

        return Forms\Components\DatePicker::make('data_sugerida')
            ->label('Data sugerida')
            ->native(false)
            ->view('filament.forms.components.data-com-feriados')
            ->weekStartsOnSunday()
            ->displayFormat('d/m/Y')
            ->closeOnDateSelection()
            ->minDate($mes->copy()->startOfMonth())
            ->maxDate($mes->copy()->endOfMonth()->startOfDay())
            ->defaultFocusedDate($mes->copy()->startOfMonth())
            ->hintIcon('heroicon-m-information-circle', tooltip: 'Em vermelho: fins de semana e feriados.')
            ->validationMessages([
                'after_or_equal' => 'Escolha uma data dentro do mês do atendimento.',
                'before_or_equal' => 'Escolha uma data dentro do mês do atendimento.',
            ]);
    }

    private function secaoAgendamento(int $ordem): Forms\Components\Section
    {
        // Cada agendamento com a sua cor (a mesma da extensão), na etiqueta e na borda.
        return Forms\Components\Section::make(new HtmlString(AgendamentoMarinha::etiquetaDaOrdem($ordem) . ' agendamento'))
            ->statePath("agendamento_{$ordem}")
            ->extraAttributes(['style' => 'border-left: 6px solid ' . AgendamentoMarinha::corDaOrdem($ordem)])
            ->compact()
            ->description(fn(Forms\Get $get) => $this->descricaoAgendamento($get('id')))
            ->schema([
                Forms\Components\Hidden::make('id'),

                Forms\Components\Repeater::make('clientes')
                    ->hiddenLabel()
                    // As linhas já vêm abertas (uma por vaga): nada de criar ou apagar linha.
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->extraItemActions([
                        Forms\Components\Actions\Action::make('limpar')
                            ->icon('heroicon-m-x-mark')
                            ->color('gray')
                            ->tooltip('Limpar esta linha (tira o cliente do agendamento)')
                            ->action(function (array $arguments, Forms\Components\Repeater $component) {
                                $estado = $component->getState();
                                $estado[$arguments['item']] = static::linhaVazia();
                                $component->state($estado);
                            }),
                    ])
                    // Um cliente por linha, rótulos só na primeira (CSS em cadastro.blade.php).
                    ->extraAttributes(['class' => 'proa-clientes'])
                    ->columns(10)
                    ->disabled(fn(Forms\Get $get) => $this->marcado($get('id')))
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        AgendamentoMarinhaResource::campoDocumento()->columnSpan(2),
                        AgendamentoMarinhaResource::campoNome()->columnSpan(3),
                        AgendamentoMarinhaResource::campoGru()->columnSpan(2),
                        AgendamentoMarinhaResource::campoServico()->columnSpan(3),
                    ]),
            ]);
    }

    public static function linhaVazia(): array
    {
        return ['id' => null, 'cliente_documento' => null, 'cliente_nome' => null, 'gru' => null, 'sisap_servico_id' => null];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('salvar')
                ->label('Salvar')
                ->icon('heroicon-o-check')
                ->submit('salvar'),
        ];
    }

    public function salvar(): void
    {
        $estado = $this->form->getState();

        // Agendamento marcado não é dehydrated (campo desabilitado): volta o id para não ser lido como removido.
        foreach ($this->data as $chave => $slot) {
            if (str_starts_with($chave, 'agendamento_')) {
                $estado[$chave]['id'] = $slot['id'] ?? null;
                $estado[$chave]['clientes'] ??= [];
            }
        }

        try {
            CadastroDoMes::salvarProcurador($this->capitaniaId, $this->competencia, ['prestador_id' => $this->prestadorId] + $estado);
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Não foi salvo')->body($e->getMessage())->danger()->persistent()->send();

            return;
        }

        Notification::make()->title('Agendamentos salvos')->success()->send();

        $this->redirect(AgendamentoMarinhaResource::getUrl('mes', ['competencia' => Carbon::parse($this->competencia)->format('Y-m')]));
    }

    public function vagasPorAgendamento(): int
    {
        return max(1, (int) $this->capitania()->sisap_vagas_por_agendamento);
    }

    private function agendamento(mixed $id): ?AgendamentoMarinha
    {
        if (!filled($id)) {
            return null;
        }

        return $this->agendamentos[(int) $id] ??= AgendamentoMarinha::with('solicitacoes')->find((int) $id);
    }

    /** Agendamento já marcado no SISAP: aparece só para consulta. */
    public function marcado(mixed $id): bool
    {
        return $this->agendamento($id)?->status === AgendamentoMarinha::STATUS_AGENDADO;
    }

    private function descricaoAgendamento(mixed $id): string
    {
        $agendamento = $this->agendamento($id);
        $vagas = $this->vagasPorAgendamento();

        if (!$agendamento) {
            return "Até {$vagas} cliente(s). Linha em branco fica de fora.";
        }

        if ($agendamento->status === AgendamentoMarinha::STATUS_AGENDADO) {
            return 'Marcado no SISAP para ' . ($agendamento->data_hora?->format('d/m/Y H:i') ?? '—')
                . " · nº {$agendamento->numero} · chave {$agendamento->chave}. Somente consulta.";
        }

        $status = AgendamentoMarinha::statuses()[$agendamento->status] ?? $agendamento->status;
        $erro = $agendamento->status === AgendamentoMarinha::STATUS_FALHOU && $agendamento->erro ? " — {$agendamento->erro}" : '';

        $recusados = $agendamento->solicitacoes
            ->filter(fn(SolicitacaoAgendamento $s) => $s->descartada())
            ->map(fn(SolicitacaoAgendamento $s) => ($s->cliente_nome ?: SolicitacaoAgendamento::formatarDocumento($s->cliente_documento)) . ": {$s->motivo_descarte}")
            ->implode(' | ');

        return "{$status}{$erro} · até {$vagas} cliente(s)."
            . ($recusados ? " Recusado pelo SISAP — corrija ou limpe a linha: {$recusados}" : '');
    }
}
