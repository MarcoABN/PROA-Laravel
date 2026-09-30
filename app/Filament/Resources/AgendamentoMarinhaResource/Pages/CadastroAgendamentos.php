<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Services\Agendamento\CadastroDoMes;
use App\Support\AcessoAgendamento;
use Carbon\Carbon;
use Closure;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use InvalidArgumentException;
use Livewire\Attributes\Locked;

/**
 * Cadastro de agendamentos de uma Capitania + Mês.
 *
 * Criar: escolhe capitania e mês (a dupla não pode repetir). Editar: capitania e mês ficam travados;
 * dá para incluir/remover procuradores e clientes e mudar data/período. Agendamentos já marcados no
 * SISAP aparecem só para consulta.
 */
class CadastroAgendamentos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AgendamentoMarinhaResource::class;

    protected static string $view = 'filament.resources.agendamento-marinha.cadastro';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /**
     * Chave do cadastro em edição (Y-m-01 e id da capitania); nulas ao criar.
     * Locked: o navegador não consegue trocá-las e salvar em outra capitania/mês.
     */
    #[Locked]
    public ?string $competenciaFixa = null;

    #[Locked]
    public ?int $capitaniaFixa = null;

    /** @var array<int, AgendamentoMarinha|null> */
    private array $agendamentos = [];

    public static function canAccess(array $parameters = []): bool
    {
        return AcessoAgendamento::permitido();
    }

    public function mount(?string $competencia = null, ?string $capitania = null): void
    {
        abort_unless(static::canAccess(), 403);

        // Editar: /cadastro/{competencia}/{capitania}. Criar com o mês já escolhido: /cadastro?mes=AAAA-MM.
        $competencia ??= request()->query('mes');

        $mes = $competencia !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $competencia) ? "{$competencia}-01" : null;
        abort_if($competencia !== null && $mes === null, 404);

        if ($mes && $capitania !== null) {
            $capitaniaModel = Capitania::find($capitania);
            abort_unless($capitaniaModel, 404);

            if (!CadastroDoMes::existe($capitaniaModel->id, $mes)) {
                Notification::make()->title("Ainda não há cadastro para {$capitaniaModel->sigla} em " . Carbon::parse($mes)->format('m/Y') . '.')->warning()->send();
                $this->redirect(AgendamentoMarinhaResource::urlNovoCadastro($competencia));

                return;
            }

            $this->competenciaFixa = $mes;
            $this->capitaniaFixa = $capitaniaModel->id;
            $this->form->fill(CadastroDoMes::carregar($capitaniaModel->id, $mes));

            return;
        }

        $mes ??= array_keys(AgendamentoMarinha::competenciasDisponiveis())[1];
        $padrao = Capitania::where('padrao', true)->value('id');

        $this->form->fill([
            'competencia' => $mes,
            // A capitania padrão só vem marcada se ainda não tiver cadastro no mês.
            'capitania_id' => $padrao && !CadastroDoMes::existe($padrao, $mes) ? $padrao : null,
            'procuradores' => [[]],
        ]);
    }

    public function editando(): bool
    {
        return $this->capitaniaFixa !== null;
    }

    public function getTitle(): string
    {
        if (!$this->editando()) {
            return 'Novo cadastro de agendamentos';
        }

        return 'Cadastro ' . Capitania::find($this->capitaniaFixa)?->sigla . ' · ' . Carbon::parse($this->competenciaFixa)->format('m/Y');
    }

    public function getBreadcrumbs(): array
    {
        $migalhas = [AgendamentoMarinhaResource::getUrl() => 'Agendamentos Marinha'];

        if ($this->editando()) {
            $mes = Carbon::parse($this->competenciaFixa);
            $migalhas[AgendamentoMarinhaResource::getUrl('mes', ['competencia' => $mes->format('Y-m')])] = $mes->format('m/Y');
        }

        $migalhas[] = $this->editando() ? 'Editar cadastro' : 'Novo cadastro';

        return $migalhas;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('voltar')
                ->label($this->editando() ? 'Voltar ao mês' : 'Meses')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => $this->editando()
                    ? AgendamentoMarinhaResource::getUrl('mes', ['competencia' => Carbon::parse($this->competenciaFixa)->format('Y-m')])
                    : AgendamentoMarinhaResource::getUrl()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Capitania e mês')
                    ->description(fn() => $this->editando()
                        ? 'Chave deste cadastro. Não pode ser alterada.'
                        : 'Chave do cadastro: cada capitania tem um cadastro por mês.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('competencia')
                            ->label('Mês do atendimento')
                            ->options(fn() => AgendamentoMarinhaResource::opcoesCompetencia($this->competenciaFixa ? Carbon::parse($this->competenciaFixa) : null))
                            ->required()
                            ->live()
                            ->native(false)
                            ->disabled(fn() => $this->editando())
                            ->dehydrated(fn() => !$this->editando())
                            // Trocou o mês e a capitania escolhida já tem cadastro nele: limpa a escolha.
                            ->afterStateUpdated(function (?string $state, Get $get, Forms\Set $set) {
                                if ($state && $get('capitania_id') && CadastroDoMes::existe((int) $get('capitania_id'), $state)) {
                                    $set('capitania_id', null);
                                }

                                // Datas sugeridas do mês anterior deixam de valer.
                                $set('procuradores', collect($get('procuradores') ?? [])
                                    ->map(fn(array $bloco) => ['data_sugerida' => null, 'segunda_data_sugerida' => null] + $bloco)
                                    ->all());
                            }),

                        Forms\Components\Select::make('capitania_id')
                            ->label('Capitania')
                            ->options(fn(Get $get) => $this->opcoesCapitanias($get('competencia')))
                            // Novo cadastro: capitania que já tem cadastro no mês não pode ser escolhida.
                            ->disableOptionWhen(fn(mixed $value, Get $get) => !$this->editando()
                                && in_array((int) $value, $this->capitaniasOcupadas($get('competencia')), true))
                            ->required()
                            ->live()
                            ->native(false)
                            ->disabled(fn() => $this->editando())
                            ->dehydrated(fn() => !$this->editando())
                            ->helperText(fn(Get $get) => $this->textoRegrasDaCapitania() ?? $this->textoCapitaniasOcupadas($get('competencia')))
                            ->rule(fn(Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                if (!$this->editando() && $value && $get('competencia') && CadastroDoMes::existe((int) $value, $get('competencia'))) {
                                    $fail('Já existe cadastro desta capitania neste mês. Abra-o em "Editar cadastro", na tela do mês.');
                                }
                            }),
                    ]),

                Forms\Components\Repeater::make('procuradores')
                    ->label('Procuradores')
                    ->addActionLabel('Adicionar procurador')
                    ->reorderable(false)
                    ->collapsible()
                    ->defaultItems(0)
                    ->itemLabel(fn(array $state) => $this->resumoDoProcurador($state))
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Select::make('prestador_id')
                                ->label('Procurador')
                                ->options(fn() => AgendamentoMarinhaResource::opcoesProcuradores())
                                ->searchable()
                                ->required()
                                ->live()
                                ->distinct()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                // Com agendamento já marcado, o procurador não pode ser trocado.
                                ->disabled(fn(Get $get) => $this->marcado($get('agendamento_1.id')) || $this->marcado($get('agendamento_2.id')))
                                ->dehydrated(),

                            $this->campoDataSugerida('data_sugerida', '1ª data sugerida',
                                'Opcional. Tentada primeiro: vale se nela couberem os dois agendamentos no período.')
                                ->live(),

                            $this->campoDataSugerida('segunda_data_sugerida', '2ª data sugerida',
                                'Opcional. Tentada se a 1ª não comportar os dois agendamentos. Sem nenhuma das duas, o PROA procura a data mais próxima da 1ª.')
                                ->disabled(fn(Get $get) => !$this->competenciaAtual() || blank($get('data_sugerida'))),

                            Forms\Components\Select::make('periodo')
                                ->label('Período preferido')
                                ->options(AgendamentoMarinha::periodos())
                                ->placeholder('Indiferente')
                                ->native(false)
                                ->hintIcon('heroicon-m-information-circle', tooltip: 'Os dois agendamentos ficam neste período sempre que houver data que comporte.'),
                        ]),

                        $this->secaoAgendamento(1),
                        $this->secaoAgendamento(2),
                    ]),
            ]);
    }

    /** Calendário restrito ao mês do atendimento, com fins de semana e feriados em vermelho. */
    private function campoDataSugerida(string $nome, string $rotulo, string $dica): Forms\Components\DatePicker
    {
        $mes = fn() => $this->competenciaAtual() ? Carbon::parse($this->competenciaAtual()) : null;

        return Forms\Components\DatePicker::make($nome)
            ->label($rotulo)
            ->native(false)
            ->view('filament.forms.components.data-com-feriados')
            ->weekStartsOnSunday()
            ->displayFormat('d/m/Y')
            ->closeOnDateSelection()
            // Só dias do mês do atendimento; o calendário já abre nele.
            ->minDate(fn() => $mes()?->startOfMonth())
            ->maxDate(fn() => $mes()?->endOfMonth()->startOfDay())
            ->defaultFocusedDate(fn() => $mes()?->startOfMonth())
            ->disabled(fn() => !$this->competenciaAtual())
            // Dicas no ícone, não em texto de ajuda: cada linha a menos conta com vários procuradores.
            ->hintIcon('heroicon-m-information-circle', tooltip: fn() => $this->competenciaAtual()
                ? "{$dica} Em vermelho: fins de semana e feriados."
                : 'Escolha primeiro o mês do atendimento.')
            ->validationMessages([
                'after_or_equal' => 'Escolha uma data dentro do mês do atendimento.',
                'before_or_equal' => 'Escolha uma data dentro do mês do atendimento.',
            ]);
    }

    private function secaoAgendamento(int $ordem): Forms\Components\Section
    {
        return Forms\Components\Section::make("{$ordem}º agendamento")
            ->statePath("agendamento_{$ordem}")
            ->compact()
            ->description(fn(Get $get) => $this->descricaoAgendamento($get("agendamento_{$ordem}.id")))
            ->schema([
                Forms\Components\Hidden::make('id'),

                Forms\Components\Repeater::make('clientes')
                    ->hiddenLabel()
                    ->addActionLabel('Adicionar cliente')
                    ->reorderable(false)
                    ->defaultItems(0)
                    ->maxItems(fn() => $this->vagasPorAgendamento())
                    // Um cliente por linha, rótulos só na primeira (CSS em cadastro.blade.php).
                    ->extraAttributes(['class' => 'proa-clientes'])
                    ->columns(10)
                    ->disabled(fn(Get $get) => $this->marcado($get('id')))
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        AgendamentoMarinhaResource::campoDocumento()->columnSpan(2),
                        AgendamentoMarinhaResource::campoNome()->columnSpan(3),
                        AgendamentoMarinhaResource::campoGru()->columnSpan(2),
                        AgendamentoMarinhaResource::campoServico()->columnSpan(3),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('salvar')
                ->label($this->editando() ? 'Salvar alterações' : 'Criar cadastro')
                ->submit('salvar'),
        ];
    }

    public function salvar(): void
    {
        $estado = $this->form->getState();
        $capitaniaId = $this->capitaniaFixa ?? (int) $estado['capitania_id'];
        $competencia = $this->competenciaFixa ?? $estado['competencia'];

        try {
            CadastroDoMes::salvar($capitaniaId, $competencia, $estado['procuradores'] ?? [], novo: !$this->editando());
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Cadastro não salvo')->body($e->getMessage())->danger()->persistent()->send();

            return;
        }

        Notification::make()->title($this->editando() ? 'Cadastro atualizado' : 'Cadastro criado')->success()->send();

        $this->redirect(AgendamentoMarinhaResource::getUrl('mes', ['competencia' => Carbon::parse($competencia)->format('Y-m')]));
    }

    public function competenciaAtual(): ?string
    {
        return $this->competenciaFixa ?? ($this->data['competencia'] ?? null);
    }

    private function capitaniaAtual(): ?Capitania
    {
        $id = $this->capitaniaFixa ?? ($this->data['capitania_id'] ?? null);

        return $id ? Capitania::find($id) : null;
    }

    /** @return array<int, int> */
    private function capitaniasOcupadas(?string $competencia): array
    {
        return $competencia ? CadastroDoMes::capitaniasComCadastro($competencia) : [];
    }

    private function opcoesCapitanias(?string $competencia): array
    {
        $opcoes = AgendamentoMarinhaResource::opcoesCapitanias();

        if ($this->editando()) {
            return $opcoes;
        }

        foreach ($this->capitaniasOcupadas($competencia) as $id) {
            if (isset($opcoes[$id])) {
                $opcoes[$id] .= ' — já cadastrada neste mês';
            }
        }

        return $opcoes;
    }

    private function textoCapitaniasOcupadas(?string $competencia): ?string
    {
        return !$this->editando() && $this->capitaniasOcupadas($competencia)
            ? 'Capitanias que já têm cadastro neste mês ficam bloqueadas: altere-as em "Editar cadastro", na tela do mês.'
            : null;
    }

    public function vagasPorAgendamento(): int
    {
        return max(1, (int) ($this->capitaniaAtual()?->sisap_vagas_por_agendamento ?? 3));
    }

    private function textoRegrasDaCapitania(): ?string
    {
        $capitania = $this->capitaniaAtual();

        return $capitania
            ? "{$capitania->sigla}: até {$capitania->sisap_vagas_por_agendamento} cliente(s) por agendamento e {$capitania->sisap_agendamentos_por_mes} agendamento(s) por procurador no mês."
            : null;
    }

    private function agendamento(mixed $id): ?AgendamentoMarinha
    {
        if (!filled($id)) {
            return null;
        }

        return $this->agendamentos[(int) $id] ??= AgendamentoMarinha::find((int) $id);
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
            return "Até {$vagas} cliente(s). Deixe vazio se não houver.";
        }

        if ($agendamento->status === AgendamentoMarinha::STATUS_AGENDADO) {
            return 'Marcado no SISAP para ' . ($agendamento->data_hora?->format('d/m/Y H:i') ?? '—')
                . " · nº {$agendamento->numero} · chave {$agendamento->chave}. Somente consulta — para desfazer, use o botão \"Excluir {$agendamento->ordem}º\" do procurador na tela do mês.";
        }

        $status = AgendamentoMarinha::statuses()[$agendamento->status] ?? $agendamento->status;
        $erro = $agendamento->status === AgendamentoMarinha::STATUS_FALHOU && $agendamento->erro ? " — {$agendamento->erro}" : '';

        return "Nº {$agendamento->ordem} · {$status}{$erro} · até {$vagas} cliente(s).";
    }

    /** Título do bloco do procurador: dá para conferir tudo com os blocos recolhidos. */
    private function resumoDoProcurador(array $bloco): string
    {
        $partes = [AgendamentoMarinhaResource::opcoesProcuradores()[$bloco['prestador_id'] ?? null] ?? 'Novo procurador'];

        $datas = collect([$bloco['data_sugerida'] ?? null, $bloco['segunda_data_sugerida'] ?? null])
            ->filter()
            ->map(fn(string $data) => Carbon::parse($data)->format('d/m'));

        if ($datas->isNotEmpty()) {
            $partes[] = $datas->implode(' ou ');
        }

        if (filled($bloco['periodo'] ?? null)) {
            $partes[] = AgendamentoMarinha::periodos()[$bloco['periodo']] ?? $bloco['periodo'];
        }

        $clientes = collect([1, 2])->map(fn(int $ordem) => count($bloco["agendamento_{$ordem}"]['clientes'] ?? []));
        $partes[] = "1º: {$clientes[0]} · 2º: {$clientes[1]} cliente(s)";

        return implode(' · ', $partes);
    }
}
