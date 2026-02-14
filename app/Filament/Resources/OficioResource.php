<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OficioResource\Pages;
use App\Models\Oficio;
use App\Models\EscolaNautica;
use App\Models\Cliente;
use App\Services\OficioService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action; // Necessário para tipagem da $action

class OficioResource extends Resource
{
    protected static ?string $model = Oficio::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $modelLabel = 'Ofícios de Aula';
    protected static ?string $navigationGroup = 'Ofícios';

    public static function form(Form $form): Form
    {
        // MANTENDO EXATAMENTE O SEU LAYOUT VISUAL
        return $form
            ->schema([
                // --- BLOCO 1: CABEÇALHO DO DOCUMENTO ---
                Forms\Components\Section::make()
                    ->schema([
                        // Linha 1: Os Atores
                        Forms\Components\Group::make()->columns(12)->schema([
                            Forms\Components\Select::make('escola_nautica_id')
                                ->label('Escola Náutica (Origem)')
                                ->options(EscolaNautica::all()->pluck('razao_social', 'id'))
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state && $escola = EscolaNautica::find($state)) {
                                        $set('instrutor_id', $escola->instrutor_id);
                                        $set('cidade_aula', $escola->cidade . ' - ' . $escola->uf);
                                    }
                                })
                                ->prefixIcon('heroicon-m-building-office-2')
                                ->columnSpan(5),

                            Forms\Components\Select::make('instrutor_id')
                                ->label('Instrutor Responsável')
                                ->relationship('instrutor', 'nome')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-m-user')
                                ->columnSpan(4),

                            Forms\Components\Select::make('capitania_id')
                                ->label('Capitania')
                                ->relationship('capitania', 'sigla')
                                ->required()
                                ->prefixIcon('heroicon-m-paper-airplane')
                                ->columnSpan(3),
                        ]),

                        // Linha 2: Logística
                        Forms\Components\Fieldset::make('Detalhes da Execução')
                            ->schema([
                                Forms\Components\DatePicker::make('data_aula')
                                    ->label('Data')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now()->addDay())
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('periodo_aula')
                                    ->label('Horário')
                                    ->default('07:00 às 14:00')
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('local_aula')
                                    ->label('Local de Realização')
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

                // --- BLOCO 2: A LISTA (CORPO) ---
                Forms\Components\Section::make('Candidatos Habilitados')
                    ->headerActions([
                        Forms\Components\Actions\Action::make('info')
                            ->icon('heroicon-m-information-circle')
                            ->label('Máx. 6 alunos')
                            ->color('gray')
                            ->disabled(),
                    ])
                    ->schema([
                        Forms\Components\Repeater::make('itens')
                            ->hiddenLabel()
                            ->relationship('itens')
                            ->schema([
                                Forms\Components\Group::make()->columns(12)->schema([
                                    Forms\Components\Placeholder::make('icon')
                                        ->hiddenLabel()
                                        ->content(fn() => new \Illuminate\Support\HtmlString('<div class="pt-2"><svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div>'))
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('cliente_id')
                                        ->hiddenLabel()
                                        ->placeholder('Selecione o candidato...')
                                        ->options(Cliente::query()->limit(50)->pluck('nome', 'id'))
                                        ->getSearchResultsUsing(fn(string $search) => Cliente::where('nome', 'ilike', "%{$search}%")->limit(20)->pluck('nome', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->columnSpan(8),

                                    Forms\Components\TextInput::make('categoria')
                                        ->hiddenLabel()
                                        ->default('ARA/MTA')
                                        ->required()
                                        ->columnSpan(3),
                                ]),
                            ])
                            ->defaultItems(1)
                            ->maxItems(6)
                            ->addActionLabel('Adicionar Candidato na Lista')
                            ->reorderableWithButtons()
                            ->collapsible(false)
                            ->itemLabel(fn(array $state): ?string => Cliente::find($state['cliente_id'] ?? null)?->nome ?? null),
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

                Tables\Columns\TextColumn::make('itens_count')
                    ->counts('itens') // <--- ADICIONE ESTA LINHA
                    ->label('Turma')
                    ->formatStateUsing(fn($state) => $state . ' Alunos')
                    ->badge()
                    ->color(fn($state) => $state == 6 ? 'success' : 'primary'),
            ])
            // ->contentGrid(...)  <-- REMOVIDO: Isso que transformava em cartões e sumia os botões
            ->actions([
                // Botão IMPRIMIR (Visível na linha)
                Tables\Actions\Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('success') // Verde
                    ->button() // Força aparência de botão
                    ->action(function (Oficio $record, \Filament\Tables\Actions\Action $action) {
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

                // Menu com Editar/Excluir (Três pontinhos)
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