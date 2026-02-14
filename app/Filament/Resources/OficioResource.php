<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OficioResource\Pages;
use App\Models\Oficio;
use App\Models\EscolaNautica;
use App\Models\Cliente;
use App\Services\OficioService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class OficioResource extends Resource
{
    protected static ?string $model = Oficio::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $modelLabel = 'Ofícios de Aula';
    protected static ?string $navigationGroup = 'Ofícios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- CABEÇALHO ---
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Group::make()->columns(12)->schema([
                            Forms\Components\Select::make('escola_nautica_id')
                                ->label('Escola Náutica')
                                ->options(EscolaNautica::all()->pluck('razao_social', 'id'))
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state && $escola = EscolaNautica::find($state)) {
                                        $set('cidade_aula', $escola->cidade . ' - ' . $escola->uf);
                                    }
                                })
                                ->prefixIcon('heroicon-m-building-office-2')
                                ->columnSpan(8),

                            Forms\Components\Select::make('capitania_id')
                                ->label('Capitania')
                                ->relationship('capitania', 'sigla')
                                ->required()
                                ->prefixIcon('heroicon-m-paper-airplane')
                                ->columnSpan(4),
                        ]),

                        Forms\Components\Fieldset::make('Detalhes da Execução')
                            ->schema([
                                Forms\Components\DatePicker::make('data_aula')
                                    ->label('Data')
                                    ->displayFormat('d/m/Y')
                                    ->default(now()->addDay())
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('periodo_aula')
                                    ->label('Horário (Global)')
                                    ->default('07:00 às 14:00')
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('local_aula')
                                    ->label('Local')
                                    ->placeholder('Ex: Lago Paranoá')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('cidade_aula')
                                    ->label('Cidade/UF')
                                    ->required()
                                    ->columnSpan(1),
                            ])
                            ->columns(5),
                    ]),

                // --- 1. ALUNOS ---
                Forms\Components\Section::make('Candidatos Habilitados')
                    ->schema([
                        Forms\Components\Repeater::make('itens')
                            ->hiddenLabel()
                            ->relationship('itens')
                            ->schema([
                                Forms\Components\Grid::make(12)->schema([
                                    Forms\Components\Select::make('cliente_id')
                                        ->hiddenLabel()
                                        ->placeholder('Selecione o candidato...')
                                        ->options(Cliente::query()->limit(50)->pluck('nome', 'id'))
                                        ->getSearchResultsUsing(fn(string $search) => Cliente::where('nome', 'ilike', "%{$search}%")->limit(20)->pluck('nome', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->columnSpan(9),

                                    Forms\Components\TextInput::make('categoria')
                                        ->hiddenLabel()
                                        ->default('ARA/MTA')
                                        ->required()
                                        ->columnSpan(3),
                                ]),
                            ])
                            ->defaultItems(1)
                            ->maxItems(6)
                            ->addActionLabel('Adicionar Candidato')
                            ->reorderableWithButtons()
                            ->itemLabel(fn (array $state): ?string => Cliente::find($state['cliente_id'] ?? null)?->nome ?? null),
                    ])
                    ->compact(),

                // --- 2. INSTRUTORES ---
                Forms\Components\Section::make('Equipe de Instrução')
                    ->description('Adicione os instrutores. Marque APENAS UM como responsável pela assinatura.')
                    ->schema([
                        Forms\Components\Repeater::make('instrutores_oficio')
                            ->hiddenLabel()
                            ->relationship('instrutores_oficio')
                            ->schema([
                                Forms\Components\Grid::make(12)->schema([
                                    
                                    Forms\Components\Select::make('prestador_id')
                                        ->hiddenLabel()
                                        ->placeholder('Selecione o instrutor...')
                                        ->relationship('prestador', 'nome', fn($query) => $query->where('is_instrutor', true))
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(9),

                                    Forms\Components\Toggle::make('is_principal')
                                        ->label('Assina Doc.?')
                                        ->inline(false)
                                        ->onColor('success')
                                        ->offColor('gray')
                                        ->columnSpan(3)
                                        // VALIDAÇÃO
                                        ->rules([
                                            fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                if ($value === true) {
                                                    $items = $get('../../instrutores_oficio');
                                                    $marcados = collect($items)->where('is_principal', true)->count();
                                                    
                                                    if ($marcados > 1) {
                                                        $fail('Apenas um instrutor pode assinar.');
                                                    }
                                                }
                                            },
                                        ]),
                                ]),
                            ])
                            ->defaultItems(1)
                            ->maxItems(4)
                            ->addActionLabel('Adicionar Instrutor')
                            ->reorderableWithButtons()
                            ->columnSpanFull(), 
                            // ->simple() <--- REMOVIDO POIS CAUSA O ERRO
                    ])
                    ->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_oficio')
                    ->label('Documento')
                    ->formatStateUsing(fn($state) => "Ofício nº {$state}")
                    ->description(fn(Oficio $record) => $record->escola->razao_social)
                    ->searchable(['sequencial', 'ano'])
                    ->sortable(['ano', 'sequencial'])
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('data_aula')
                    ->label('Execução')
                    ->date('d/m/Y')
                    ->description(fn(Oficio $record) => $record->local_aula)
                    ->sortable(),

                Tables\Columns\TextColumn::make('capitania.sigla')
                    ->label('Destino')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('instrutores_oficio_count')
                    ->counts('instrutores_oficio')
                    ->label('Instrutores')
                    ->badge(),

                Tables\Columns\TextColumn::make('itens_count')
                    ->counts('itens')
                    ->label('Turma')
                    ->formatStateUsing(fn($state) => $state . ' Alunos')
                    ->badge()
                    ->color(fn($state) => $state == 6 ? 'success' : 'primary'),
            ])
            ->actions([
                Tables\Actions\Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->button()
                    ->action(function (Oficio $record, Action $action) {
                        try {
                            $pdfPath = app(OficioService::class)->gerarDocumento($record);
                            $fileName = basename($pdfPath);
                            $url = asset("storage/documentos_gerados/{$fileName}");
                            $action->getLivewire()->js("window.open('{$url}', '_blank');");
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    }),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOficios::route('/'),
            'create' => Pages\CreateOficio::route('/create'),
            'edit' => Pages\EditOficio::route('/{record}/edit'),
        ];
    }
}