<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgendamentoMarinhaResource\Pages;
use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Models\Cliente;
use App\Models\Prestador;
use App\Models\SisapServico;
use App\Models\SolicitacaoAgendamento;
use App\Services\Agendamento\CadastroDoMes;
use App\Services\Agendamento\RegistraResultadoAgendamento;
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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * Agendamento na Marinha:
 * 1) tela de meses (Pages\ListMesesAgendamento);
 * 2) cadastro de uma Capitania + Mês (Pages\CadastroAgendamentos) — onde se incluem procuradores e clientes;
 * 3) o mês aberto (Pages\VerMesAgendamento), para consulta e operação, agrupado por capitania · procurador.
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

    /** Inclusão e alteração de clientes acontecem só no cadastro da capitania/mês. */
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['agendamento', 'prestador', 'capitania', 'servico']))
            // Ag. 1 antes do Ag. 2 dentro de cada procurador.
            ->defaultSort('agendamento_marinha_id')
            // Hierarquia: mês (a página) → capitania + procurador (o grupo) → serviços.
            ->defaultGroup(
                Group::make('capitania_id')
                    ->titlePrefixedWithLabel(false)
                    ->getKeyFromRecordUsing(fn(SolicitacaoAgendamento $record) => "{$record->capitania_id}-{$record->prestador_id}")
                    ->orderQueryUsing(fn(Builder $query, string $direction) => $query
                        ->orderBy('capitania_id', $direction)
                        ->orderBy('prestador_id', $direction))
                    ->scopeQueryByKeyUsing(function (Builder $query, string $chave) {
                        [$capitaniaId, $prestadorId] = array_pad(explode('-', $chave, 2), 2, null);

                        return $query->where('capitania_id', $capitaniaId)->where('prestador_id', $prestadorId);
                    })
                    ->getTitleFromRecordUsing(fn(SolicitacaoAgendamento $record) => ($record->capitania?->sigla ?? 'Capitania')
                        . ' · ' . ($record->prestador?->nome ?? 'Procurador'))
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
                    ->formatStateUsing(fn($state) => "{$state}º"),

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
                Tables\Filters\SelectFilter::make('capitania_id')
                    ->label('Capitania')
                    ->relationship('capitania', 'sigla'),

                Tables\Filters\SelectFilter::make('prestador_id')
                    ->label('Procurador')
                    ->relationship('prestador', 'nome', fn(Builder $query) => $query->where('is_procurador', true)),

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
                    Tables\Actions\Action::make('editarCadastro')
                        ->label('Editar cadastro')
                        ->icon('heroicon-o-pencil-square')
                        ->url(fn(SolicitacaoAgendamento $record) => static::urlCadastro($record->capitania_id, $record->competencia)),

                    Tables\Actions\Action::make('registrarResultado')
                        ->label('Registrar agendamento')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('success')
                        ->modalDescription(fn(SolicitacaoAgendamento $record) => "Vale para todos os clientes do {$record->agendamento?->ordem}º agendamento de {$record->prestador?->nome}.")
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

                    // Só este cliente. O agendamento inteiro se exclui pelo botão no cabeçalho do procurador.
                    Tables\Actions\Action::make('excluirCliente')
                        ->label('Excluir cliente')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn(SolicitacaoAgendamento $record) => $record->editavel())
                        ->requiresConfirmation()
                        ->modalHeading(fn(SolicitacaoAgendamento $record) => 'Excluir ' . ($record->cliente_nome ?: SolicitacaoAgendamento::formatarCpf($record->cliente_cpf))
                            . " do {$record->agendamento?->ordem}º agendamento?")
                        ->modalDescription(function (SolicitacaoAgendamento $record) {
                            $ultimo = $record->agendamento?->solicitacoes()->whereKeyNot($record->id)->doesntExist();

                            return "O cliente é apagado do PROA e a GRU {$record->gru} pode ser usada de novo."
                                . ($ultimo ? " Ele é o único cliente: o {$record->agendamento?->ordem}º agendamento de {$record->prestador?->nome} também é excluído e a cota é liberada." : '');
                        })
                        ->modalSubmitActionLabel('Excluir')
                        ->action(function (SolicitacaoAgendamento $record) {
                            try {
                                $agendamentoExcluido = CadastroDoMes::excluirCliente($record);
                            } catch (\InvalidArgumentException $e) {
                                Notification::make()->title('Cliente não excluído')->body($e->getMessage())->danger()->send();

                                return;
                            }

                            Notification::make()
                                ->title($agendamentoExcluido ? 'Cliente excluído. O agendamento ficou vazio e também foi excluído.' : 'Cliente excluído.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    /**
     * Novo cadastro, opcionalmente já com o mês escolhido. O mês vai na query string: com só o primeiro
     * dos dois parâmetros opcionais da rota, o Livewire falha ao montar a página.
     */
    public static function urlNovoCadastro(?string $competencia = null): string
    {
        $url = static::getUrl('cadastro');

        return $competencia ? $url . '?mes=' . Carbon::parse(strlen($competencia) === 7 ? "{$competencia}-01" : $competencia)->format('Y-m') : $url;
    }

    public static function urlCadastro(int $capitaniaId, mixed $competencia): string
    {
        return static::getUrl('cadastro', [
            'competencia' => Carbon::parse($competencia)->format('Y-m'),
            'capitania' => $capitaniaId,
        ]);
    }

    // --- Campos dos clientes, usados no cadastro (Pages\CadastroAgendamentos) ---

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

    public static function opcoesCompetencia(?Carbon $atual = null): array
    {
        $opcoes = AgendamentoMarinha::competenciasDisponiveis();

        if ($atual) {
            $opcoes = [$atual->toDateString() => $atual->format('m/Y')] + $opcoes;
        }

        return $opcoes;
    }

    /**
     * Linha abaixo de "CAPITANIA · Procurador": um resumo de cada agendamento dele no mês, com o botão
     * que exclui o agendamento inteiro (ação excluirAgendamento da página do mês).
     */
    private static function resumoProcurador(SolicitacaoAgendamento $record): HtmlString
    {
        $agendamentos = AgendamentoMarinha::withCount('solicitacoes')
            ->with('capitania')
            ->where('prestador_id', $record->prestador_id)
            ->where('capitania_id', $record->capitania_id)
            ->whereDate('competencia', $record->competencia->toDateString())
            ->where('status', '!=', AgendamentoMarinha::STATUS_CANCELADO)
            ->orderBy('ordem')
            ->get();

        $resumo = $agendamentos
            ->values()
            ->map(fn(AgendamentoMarinha $a, int $i) => e(($i + 1) . "º: {$a->solicitacoes_count}/{$a->vagasTotais()} · "
                . (AgendamentoMarinha::statuses()[$a->status] ?? $a->status))
                . ' ' . static::botaoExcluirAgendamento($a, $i + 1))
            ->implode('  |  ');

        $preferencia = $agendamentos->first()?->rotuloPreferencia();

        return new HtmlString($preferencia ? "{$resumo}  —  " . e($preferencia) : $resumo);
    }

    private static function botaoExcluirAgendamento(AgendamentoMarinha $agendamento, int $posicao): string
    {
        // .stop: o clique no botão não recolhe o grupo.
        return Blade::render(
            '<x-filament::link tag="button" color="danger" size="sm" icon="heroicon-m-trash" x-on:click.stop="null" wire:click="mountAction(\'excluirAgendamento\', { agendamento: {{ $id }} })">Excluir {{ $posicao }}º</x-filament::link>',
            ['id' => $agendamento->id, 'posicao' => $posicao],
        );
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
            // Antes de "mes": senão "/cadastro" seria lido como um mês.
            'cadastro' => Pages\CadastroAgendamentos::route('/cadastro/{competencia?}/{capitania?}'),
            'mes' => Pages\VerMesAgendamento::route('/{competencia}'),
        ];
    }
}
