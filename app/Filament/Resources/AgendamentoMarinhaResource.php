<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgendamentoMarinhaResource\Pages;
use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Models\Cliente;
use App\Models\Prestador;
use App\Models\SisapServico;
use App\Models\SolicitacaoAgendamento;
use App\Services\Agendamento\AlocaSolicitacao;
use App\Services\Agendamento\RegistraResultadoAgendamento;
use App\Services\Agendamento\SemVagaException;
use App\Support\AcessoAgendamento;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agendamento na Marinha em dois níveis:
 * 1) tela de meses (Pages\ListMesesAgendamento);
 * 2) o mês aberto (Pages\VerMesAgendamento), com os serviços agrupados por procurador.
 */
class AgendamentoMarinhaResource extends Resource
{
    protected static ?string $model = SolicitacaoAgendamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Agendamentos Marinha';
    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'agendamentos-marinha';
    protected static ?string $modelLabel = 'serviço para agendar';
    protected static ?string $pluralModelLabel = 'Agendamentos Marinha';
    protected static bool $hasTitleCaseModelLabel = false;

    public static function canAccess(): bool
    {
        return AcessoAgendamento::permitido();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return AcessoAgendamento::permitido();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\Select::make('prestador_id')
                    ->label('Procurador')
                    ->options(fn() => static::opcoesProcuradores())
                    ->searchable()
                    ->required()
                    ->live()
                    ->helperText(fn(Get $get, ?SolicitacaoAgendamento $record) => static::textoVagas(
                        $get('prestador_id'), $get('capitania_id'), $get('competencia'), $record,
                    ))
                    ->rule(fn(Get $get, ?SolicitacaoAgendamento $record) => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                        if ($value && $get('capitania_id') && $get('competencia')
                            && AlocaSolicitacao::vagasLivres((int) $value, (int) $get('capitania_id'), $get('competencia'), $record) < 1) {
                            $fail('Este procurador não tem mais vagas nesta capitania neste mês.');
                        }
                    }),

                Forms\Components\Select::make('capitania_id')
                    ->label('Capitania')
                    ->options(fn() => static::opcoesCapitanias())
                    ->default(fn() => Capitania::where('padrao', true)->value('id'))
                    ->required()
                    ->live()
                    ->native(false),

                Forms\Components\Select::make('competencia')
                    ->label('Mês do atendimento')
                    ->options(fn(?SolicitacaoAgendamento $record, $livewire) => static::opcoesCompetencia(
                        $record?->competencia ?? static::competenciaDaPagina($livewire),
                    ))
                    ->default(fn($livewire) => static::competenciaDaPagina($livewire)?->toDateString()
                        ?? array_keys(AgendamentoMarinha::competenciasDisponiveis())[1])
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->toDateString() : null)
                    ->required()
                    ->live()
                    ->native(false),

                static::campoDataSugerida('competencia')
                    ->afterStateHydrated(function ($component, ?SolicitacaoAgendamento $record) {
                        if ($record) {
                            $component->state($record->agendamento?->data_sugerida?->toDateString());
                        }
                    }),

                static::campoPeriodo()
                    ->afterStateHydrated(function ($component, ?SolicitacaoAgendamento $record) {
                        if ($record) {
                            $component->state($record->agendamento?->periodo);
                        }
                    }),

                static::campoServico(),
                static::campoCpf(),
                static::campoNome(),
                static::campoGru()->unique(ignoreRecord: true),
            ]);
    }

    /**
     * @param  string  $caminhoCompetencia  caminho relativo do campo de mês (ex.: '../../competencia' dentro de repeater)
     */
    public static function campoDataSugerida(string $caminhoCompetencia): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('data_sugerida')
            ->label('Data sugerida')
            ->native(false)
            ->displayFormat('d/m/Y')
            ->closeOnDateSelection()
            ->helperText('Opcional. O PROA tenta esta data; sem vaga, a mais próxima. Vale para os agendamentos do procurador nesta capitania e mês.')
            ->rule(fn(Get $get) => function (string $attribute, $value, Closure $fail) use ($get, $caminhoCompetencia) {
                $mes = $get($caminhoCompetencia);

                if ($value && $mes && Carbon::parse($value)->format('Y-m') !== Carbon::parse($mes)->format('Y-m')) {
                    $fail('Escolha uma data dentro do mês do atendimento.');
                }
            });
    }

    public static function campoPeriodo(): Forms\Components\Select
    {
        return Forms\Components\Select::make('periodo')
            ->label('Período preferido')
            ->options(AgendamentoMarinha::periodos())
            ->placeholder('Indiferente')
            ->native(false)
            ->helperText('Os dois agendamentos do procurador ficam neste período sempre que houver data que comporte.');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['agendamento', 'prestador', 'capitania', 'servico']))
            // Ag. 1 antes do Ag. 2 dentro de cada procurador.
            ->defaultSort('agendamento_marinha_id')
            ->defaultGroup(
                Group::make('prestador_id')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn(SolicitacaoAgendamento $record) => $record->prestador?->nome ?? 'Procurador')
                    ->getDescriptionFromRecordUsing(fn(SolicitacaoAgendamento $record) => static::resumoProcurador($record))
                    ->collapsible()
            )
            ->groupingSettingsHidden()
            // Clique na linha não abre nada: o clique em CPF e GRU é para copiar.
            ->recordAction(null)
            ->recordUrl(null)
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('agendamento.ordem')
                    ->label('Agendamento')
                    ->formatStateUsing(fn($state, SolicitacaoAgendamento $record) => "Nº {$state} · {$record->capitania?->sigla}"),

                Tables\Columns\TextColumn::make('cliente_nome')
                    ->label('Cliente')
                    ->placeholder('—')
                    ->searchable(query: function (Builder $query, string $search) {
                        $digitos = SolicitacaoAgendamento::somenteDigitos($search);

                        $query->where(fn(Builder $q) => $q
                            ->where('cliente_nome', 'ilike', "%{$search}%")
                            ->when($digitos !== '', fn(Builder $q) => $q
                                ->orWhere('cliente_cpf', 'like', "%{$digitos}%")
                                ->orWhere('gru', 'like', "%{$digitos}%")));
                    }),

                Tables\Columns\TextColumn::make('cliente_cpf')
                    ->label('CPF')
                    ->formatStateUsing(fn(string $state) => SolicitacaoAgendamento::formatarCpf($state))
                    ->copyable()
                    ->copyableState(fn(SolicitacaoAgendamento $record) => $record->cliente_cpf)
                    ->copyMessage('CPF copiado')
                    ->icon('heroicon-m-document-duplicate')
                    ->iconPosition(IconPosition::After)
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('gru')
                    ->label('GRU')
                    ->copyable()
                    ->copyMessage('GRU copiada')
                    ->icon('heroicon-m-document-duplicate')
                    ->iconPosition(IconPosition::After)
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('servico.sigla')
                    ->label('Serviço')
                    ->badge()
                    ->tooltip(fn(SolicitacaoAgendamento $record) => $record->servico?->descricao_sisap),

                Tables\Columns\TextColumn::make('agendamento.status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => AgendamentoMarinha::statuses()[$state] ?? $state)
                    ->color(fn(string $state) => AgendamentoMarinha::coresStatus()[$state] ?? 'gray')
                    ->description(fn(SolicitacaoAgendamento $record) => static::detalheAgendamento($record->agendamento)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('prestador_id')
                    ->label('Procurador')
                    ->relationship('prestador', 'nome', fn(Builder $query) => $query->where('is_procurador', true)),

                Tables\Filters\SelectFilter::make('capitania_id')
                    ->label('Capitania')
                    ->relationship('capitania', 'sigla'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(AgendamentoMarinha::statuses())
                    ->query(fn(Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn(Builder $q, string $valor) => $q->whereHas('agendamento', fn(Builder $a) => $a->where('status', $valor)),
                    )),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->visible(fn(SolicitacaoAgendamento $record) => $record->editavel())
                        ->using(fn(SolicitacaoAgendamento $record, array $data, $action) => static::salvar($data, $record, $action)),

                    Tables\Actions\Action::make('registrarResultado')
                        ->label('Registrar agendamento')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('success')
                        ->modalDescription(fn(SolicitacaoAgendamento $record) => "Vale para todos os serviços do agendamento nº {$record->agendamento?->ordem} de {$record->prestador?->nome}.")
                        ->visible(fn(SolicitacaoAgendamento $record) => in_array($record->agendamento?->status, [
                            AgendamentoMarinha::STATUS_PENDENTE, AgendamentoMarinha::STATUS_FALHOU,
                        ]))
                        ->form([
                            Forms\Components\DateTimePicker::make('data_hora')
                                ->label('Data e hora')
                                ->seconds(false)
                                ->required(),
                            Forms\Components\TextInput::make('numero')
                                ->label('Nº do agendamento')
                                ->placeholder('483-000836/2026')
                                ->required(),
                            Forms\Components\TextInput::make('chave')
                                ->label('Chave de confirmação')
                                ->required(),
                        ])
                        ->action(fn(SolicitacaoAgendamento $record, array $data) => RegistraResultadoAgendamento::registrar(
                            $record->agendamento,
                            numero: $data['numero'],
                            chave: $data['chave'],
                            dataHora: Carbon::parse($data['data_hora']),
                        )),

                    Tables\Actions\Action::make('cancelarAgendamento')
                        ->label('Cancelar agendamento')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Cancela o agendamento inteiro no PROA e libera a cota do procurador. Se já estava marcado, cancele também no SISAP (até 24h antes).')
                        ->visible(fn(SolicitacaoAgendamento $record) => $record->agendamento?->status !== AgendamentoMarinha::STATUS_CANCELADO)
                        ->action(fn(SolicitacaoAgendamento $record) => $record->agendamento->update([
                            'status' => AgendamentoMarinha::STATUS_CANCELADO,
                        ])),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn(SolicitacaoAgendamento $record) => $record->editavel())
                        ->using(fn(SolicitacaoAgendamento $record) => AlocaSolicitacao::remover($record)),
                ]),
            ]);
    }

    // --- Campos reaproveitados no cadastro em lote (Pages\ListMesesAgendamento) ---

    public static function campoServico(): Forms\Components\Select
    {
        return Forms\Components\Select::make('sisap_servico_id')
            ->label('Serviço')
            ->options(fn() => SisapServico::ativos()->orderBy('sigla')->pluck('sigla', 'id'))
            ->searchable()
            ->required();
    }

    public static function campoCpf(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('cliente_cpf')
            ->label('CPF do cliente')
            ->mask('999.999.999-99')
            ->stripCharacters(['.', '-'])
            ->formatStateUsing(fn(?string $state) => $state ? SolicitacaoAgendamento::formatarCpf($state) : null)
            ->required()
            ->rule(fn() => function (string $attribute, $value, Closure $fail) {
                if (!SolicitacaoAgendamento::cpfValido($value)) {
                    $fail('CPF inválido.');
                }
            })
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                if (blank($get('cliente_nome'))) {
                    $set('cliente_nome', Cliente::where('cpfcnpj', SolicitacaoAgendamento::somenteDigitos($state))->value('nome'));
                }
            });
    }

    public static function campoNome(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('cliente_nome')
            ->label('Nome do cliente')
            ->maxLength(255);
    }

    public static function campoGru(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('gru')
            ->label('Número da GRU')
            ->mask('999999999999999999')
            ->required()
            ->length(18)
            ->validationMessages([
                'size' => 'A GRU deve ter 18 dígitos.',
                'unique' => 'Esta GRU já foi cadastrada.',
            ]);
    }

    public static function opcoesProcuradores(): array
    {
        return Prestador::where('is_procurador', true)->orderBy('nome')->pluck('nome', 'id')->all();
    }

    public static function opcoesCapitanias(): array
    {
        return Capitania::orderByDesc('padrao')->orderBy('nome')->pluck('nome', 'id')->all();
    }

    public static function textoVagas(mixed $prestadorId, mixed $capitaniaId, mixed $competencia, ?SolicitacaoAgendamento $record = null): ?string
    {
        if (!$prestadorId || !$capitaniaId || !$competencia) {
            return null;
        }

        $capitania = Capitania::find($capitaniaId);
        $livres = AlocaSolicitacao::vagasLivres((int) $prestadorId, (int) $capitaniaId, $competencia, $record);
        $total = (int) $capitania?->sisap_vagas_por_agendamento * (int) $capitania?->sisap_agendamentos_por_mes;

        return "Vagas livres neste mês: {$livres} de {$total}.";
    }

    /**
     * Grava pelo serviço de alocação; sem vaga, avisa e mantém o formulário aberto.
     */
    public static function salvar(array $data, ?SolicitacaoAgendamento $record, $action): SolicitacaoAgendamento
    {
        try {
            return AlocaSolicitacao::salvar($data, $record);
        } catch (SemVagaException | \InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $action->halt();

            throw $e;
        }
    }

    public static function opcoesCompetencia(?Carbon $atual = null): array
    {
        $opcoes = AgendamentoMarinha::competenciasDisponiveis();

        if ($atual) {
            $opcoes = [$atual->toDateString() => $atual->format('m/Y')] + $opcoes;
        }

        return $opcoes;
    }

    private static function competenciaDaPagina($livewire): ?Carbon
    {
        return $livewire instanceof Pages\VerMesAgendamento ? $livewire->dataCompetencia() : null;
    }

    /**
     * Linha abaixo do nome do procurador: um resumo de cada agendamento dele no mês.
     */
    private static function resumoProcurador(SolicitacaoAgendamento $record): string
    {
        $agendamentos = AgendamentoMarinha::with('capitania')
            ->withCount('solicitacoes')
            ->where('prestador_id', $record->prestador_id)
            ->whereDate('competencia', $record->competencia->toDateString())
            ->orderBy('ordem')
            ->get();

        $resumo = $agendamentos
            ->map(fn(AgendamentoMarinha $a) => "Nº {$a->ordem} {$a->capitania?->sigla}: {$a->solicitacoes_count}/{$a->vagasTotais()} · "
                . (AgendamentoMarinha::statuses()[$a->status] ?? $a->status))
            ->implode('  |  ');

        $preferencias = $agendamentos
            ->filter(fn(AgendamentoMarinha $a) => $a->rotuloPreferencia())
            ->map(fn(AgendamentoMarinha $a) => "{$a->capitania?->sigla} " . $a->rotuloPreferencia())
            ->unique()
            ->implode('  |  ');

        return $preferencias ? "{$resumo}  —  {$preferencias}" : $resumo;
    }

    private static function detalheAgendamento(?AgendamentoMarinha $agendamento): ?string
    {
        if (!$agendamento) {
            return null;
        }

        $partes = [];

        if ($agendamento->data_hora) {
            $partes[] = $agendamento->data_hora->format('d/m/Y H:i');
        }

        if ($agendamento->numero) {
            $partes[] = "nº {$agendamento->numero} · chave {$agendamento->chave}";
        }

        if ($agendamento->status === AgendamentoMarinha::STATUS_FALHOU && $agendamento->erro) {
            $partes[] = $agendamento->erro;
        }

        return $partes ? implode(' — ', $partes) : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMesesAgendamento::route('/'),
            'mes' => Pages\VerMesAgendamento::route('/{competencia}'),
        ];
    }
}
