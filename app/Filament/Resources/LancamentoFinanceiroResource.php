<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LancamentoFinanceiroResource\Pages;
use App\Filament\Resources\LancamentoFinanceiroResource\RelationManagers;
use App\Models\CategoriaFinanceira;
use App\Models\LancamentoFinanceiro;
use App\Models\User;
use App\Support\AcessoFinanceiro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class LancamentoFinanceiroResource extends Resource
{
    protected static ?string $model = LancamentoFinanceiro::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Lançamentos';
    protected static ?string $navigationGroup = 'Gestão Financeira';
    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'lancamentos-financeiros';
    protected static ?string $modelLabel = 'Lançamento';
    protected static ?string $pluralModelLabel = 'Lançamentos';

    public static function canAccess(): bool
    {
        return AcessoFinanceiro::permitido();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return AcessoFinanceiro::permitido();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Lançamento')
                            ->schema([
                                Forms\Components\Select::make('tipo')
                                    ->label('Tipo')
                                    ->options(LancamentoFinanceiro::tipos())
                                    ->default(LancamentoFinanceiro::TIPO_SAIDA)
                                    ->required()
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    // O Select do Filament não confere sozinho se o valor
                                    // enviado está entre as opções: sem esta regra dá para
                                    // gravar um tipo inexistente forjando o campo, e o
                                    // consolidado (que soma por 'entrada'/'saida') perderia
                                    // o lançamento de vista.
                                    ->rule(Rule::in(array_keys(LancamentoFinanceiro::tipos())))
                                    // Trocar entrada/saída invalida a categoria escolhida,
                                    // já que cada categoria pertence a um tipo só.
                                    ->afterStateUpdated(fn(Forms\Set $set) => $set('categoria_financeira_id', null)),

                                Forms\Components\Select::make('categoria_financeira_id')
                                    ->label('Tipo de Entrada / Despesa')
                                    ->options(fn(Forms\Get $get) => $get('tipo')
                                        ? CategoriaFinanceira::ativas()
                                            ->where('tipo', $get('tipo'))
                                            ->orderBy('nome')
                                            ->pluck('nome', 'id')
                                        : [])
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->helperText('É o que agrupa os maiores gastos no consolidado.')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nome')
                                            ->label('Nome')
                                            ->required(),
                                        Forms\Components\Textarea::make('descricao')
                                            ->label('Descrição')
                                            ->rows(2),
                                    ])
                                    ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                        return CategoriaFinanceira::create([
                                            'nome' => $data['nome'],
                                            'descricao' => $data['descricao'] ?? null,
                                            'tipo' => $get('tipo'),
                                        ])->getKey();
                                    }),

                                Forms\Components\TextInput::make('descricao')
                                    ->label('Descrição')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor')
                                    ->prefix('R$')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step('0.01')
                                    ->required(),

                                Forms\Components\DatePicker::make('data_lancamento')
                                    ->label('Data')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),

                                Forms\Components\Select::make('forma_pagamento')
                                    ->label('Forma de Pagamento')
                                    ->options(LancamentoFinanceiro::formasPagamento())
                                    ->default(LancamentoFinanceiro::PAGAMENTO_DINHEIRO)
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->required()
                                    ->rule(Rule::in(array_keys(LancamentoFinanceiro::formasPagamento()))),
                            ])->columns(2),

                        Forms\Components\Section::make('Comprovantes')
                            ->description('Anexe notas, recibos ou prints. PDF, JPG, PNG ou WEBP até 10 MB por arquivo.')
                            ->schema([
                                Forms\Components\FileUpload::make('anexos')
                                    ->label('Arquivos')
                                    ->multiple()
                                    ->disk('public')
                                    ->directory('financeiro/comprovantes')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp',
                                    ])
                                    ->maxSize(10240)
                                    ->openable()
                                    ->downloadable()
                                    ->reorderable()
                                    ->appendFiles()
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('A quem pertence')
                            ->schema([
                                Forms\Components\Select::make('escopo')
                                    ->label('Fluxo')
                                    ->options(LancamentoFinanceiro::escopos())
                                    ->default(LancamentoFinanceiro::ESCOPO_USUARIO)
                                    ->required()
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->rule(Rule::in(array_keys(LancamentoFinanceiro::escopos())))
                                    ->helperText('Empresa separa os gastos gerais do fluxo pessoal de cada pessoa.'),

                                // O administrador lança em nome dos outros, mas não
                                // aparece aqui: ele não tem fluxo próprio.
                                Forms\Components\Select::make('user_id')
                                    ->label('Usuário')
                                    ->options(fn() => User::recebeLancamento()->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->default(fn() => auth()->user()?->podeReceberLancamento() ? auth()->id() : null)
                                    ->visible(fn(Forms\Get $get) => $get('escopo') === LancamentoFinanceiro::ESCOPO_USUARIO)
                                    ->required(fn(Forms\Get $get) => $get('escopo') === LancamentoFinanceiro::ESCOPO_USUARIO)
                                    // Cinto de segurança: o valor não chega ao banco
                                    // nem se alguém forjar o campo no navegador.
                                    ->rule(fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                        if ($value && ! User::find($value)?->podeReceberLancamento()) {
                                            $fail('O administrador não pode receber lançamentos. Escolha outro usuário ou use o fluxo Empresa.');
                                        }
                                    })
                                    ->helperText(fn() => auth()->user()?->podeReceberLancamento()
                                        ? null
                                        : 'Você lança em nome de outra pessoa ou no fluxo da Empresa.'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn(Builder $query) => $query
                ->orderByDesc('lancamentos_financeiros.data_lancamento')
                ->orderByDesc('lancamentos_financeiros.id'))
            ->columns([
                Tables\Columns\TextColumn::make('data_lancamento')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => LancamentoFinanceiro::tipos()[$state] ?? $state)
                    ->color(fn(string $state): string => $state === LancamentoFinanceiro::TIPO_ENTRADA ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->wrap()
                    ->description(fn(LancamentoFinanceiro $record) => $record->categoria?->nome),

                Tables\Columns\TextColumn::make('responsavel')
                    ->label('Pertence a')
                    ->badge()
                    ->color(fn(LancamentoFinanceiro $record) => $record->escopo === LancamentoFinanceiro::ESCOPO_EMPRESA ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->alignEnd()
                    ->sortable()
                    ->color(fn(LancamentoFinanceiro $record) => $record->tipo === LancamentoFinanceiro::TIPO_ENTRADA ? 'success' : 'danger')
                    ->weight('bold')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')->money('BRL')),

                // O state precisa vir de ->state(): a coluna recebe o array de anexos e,
                // sem isso, o Filament desenha um badge por arquivo em vez da contagem.
                Tables\Columns\TextColumn::make('anexos')
                    ->label('Anexos')
                    ->state(fn(LancamentoFinanceiro $record): int => count($record->anexos ?? []))
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'primary' : 'gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('forma_pagamento')
                    ->label('Pagamento')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('registradoPor.name')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(LancamentoFinanceiro::tipos()),

                Tables\Filters\SelectFilter::make('escopo')
                    ->label('Fluxo')
                    ->options(LancamentoFinanceiro::escopos()),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Usuário')
                    ->options(fn() => User::recebeLancamento()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('categoria_financeira_id')
                    ->label('Tipo de Entrada / Despesa')
                    ->relationship('categoria', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('de')
                            ->label('De')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('ate')
                            ->label('Até')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when($data['de'] ?? null, fn(Builder $q, $date) => $q->whereDate('data_lancamento', '>=', $date))
                        ->when($data['ate'] ?? null, fn(Builder $q, $date) => $q->whereDate('data_lancamento', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicadores = [];

                        if ($data['de'] ?? null) {
                            $indicadores[] = 'De ' . \Carbon\Carbon::parse($data['de'])->format('d/m/Y');
                        }

                        if ($data['ate'] ?? null) {
                            $indicadores[] = 'Até ' . \Carbon\Carbon::parse($data['ate'])->format('d/m/Y');
                        }

                        return $indicadores;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['categoria', 'user']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLancamentoFinanceiros::route('/'),
            'create' => Pages\CreateLancamentoFinanceiro::route('/create'),
            'edit'   => Pages\EditLancamentoFinanceiro::route('/{record}/edit'),
        ];
    }
}
