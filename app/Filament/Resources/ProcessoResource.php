<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcessoResource\Pages;
use App\Filament\Resources\ProcessoResource\RelationManagers;
use App\Models\Processo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProcessoResource extends Resource
{
    protected static ?string $model = Processo::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Processos';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Dados do Processo')
                            ->schema([
                                Forms\Components\TextInput::make('titulo')
                                    ->label('Título do Serviço')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('cliente_id')
                                    ->relationship('cliente', 'nome')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(fn(Forms\Set $set) => $set('embarcacao_id', null)),

                                Forms\Components\Select::make('embarcacao_id')
                                    ->label('Embarcação (Opcional)')
                                    ->options(function (Forms\Get $get) {
                                        $clienteId = $get('cliente_id');
                                        if (!$clienteId)
                                            return [];
                                        return \App\Models\Embarcacao::where('cliente_id', $clienteId)
                                            ->pluck('nome_embarcacao', 'id');
                                    })
                                    ->searchable()
                                    ->preload(),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Controle')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'triagem' => 'Triagem / Início',
                                        'analise' => 'Em Análise',
                                        'aguardando_cliente' => 'Aguardando Cliente',
                                        'protocolado' => 'Protocolado na Marinha',
                                        'exigencia' => 'Com Exigência',
                                        'concluido' => 'Concluído',
                                        'arquivado' => 'Arquivado',
                                    ])
                                    ->default('triagem')
                                    ->required(), // A lógica de log automático no Model cuidará do registro

                                Forms\Components\Select::make('prioridade')
                                    ->options([
                                        'baixa' => 'Baixa',
                                        'normal' => 'Normal',
                                        'alta' => 'Alta',
                                        'urgente' => 'Urgente',
                                    ])
                                    ->default('normal')
                                    ->required(),

                                // ALTERAÇÃO 1: Formatação da data
                                Forms\Components\DatePicker::make('prazo_estimado')
                                    ->label('Prazo Legal/Estimado')
                                    ->displayFormat('d M, Y') // Exibe: 24 Jan, 2026
                                    ->native(false), // Importante ser false para o displayFormat funcionar bem

                                Forms\Components\Hidden::make('user_id')
                                    ->default(auth()->id()),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn(Processo $record) => $record->cliente->nome),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'triagem' => 'gray',
                        'analise' => 'info',
                        'protocolado' => 'warning',
                        'exigencia' => 'danger',
                        'concluido' => 'success',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'triagem' => 'Triagem',
                        'analise' => 'Em Análise',
                        'aguardando_cliente' => 'Aguar. Cliente',
                        'protocolado' => 'Protocolado',
                        'exigencia' => 'Exigência',
                        'concluido' => 'Concluído',
                        'arquivado' => 'Arquivado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('prioridade')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'urgente' => 'danger',
                        'alta' => 'warning',
                        'normal' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('prazo_estimado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(
                        fn(Processo $record) =>
                            // Se existe prazo, já passou de hoje (começo do dia) E não está finalizado:
                        ($record->prazo_estimado && $record->prazo_estimado < now()->startOfDay() && !in_array($record->status, ['concluido', 'arquivado']))
                        ? 'danger' // Fica Vermelho
                        : 'gray'   // Fica Cinza (Padrão)
                    )
                    ->description(function (Processo $record) {
                        if (!$record->prazo_estimado)
                            return null;
                        return $record->prazo_estimado->diffForHumans();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            // ALTERAÇÃO 2: Filtros Completos
            ->filters([
                // Filtro de Status
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'triagem' => 'Triagem',
                        'analise' => 'Em Análise',
                        'aguardando_cliente' => 'Aguardando Cliente',
                        'protocolado' => 'Protocolado',
                        'exigencia' => 'Com Exigência',
                        'concluido' => 'Concluído',
                    ]),

                // Filtro de Prioridade
                Tables\Filters\SelectFilter::make('prioridade')
                    ->options([
                        'normal' => 'Normal',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ]),

                // Filtro de Vencidos (Customizado)
                Tables\Filters\Filter::make('vencidos')
                    ->label('Apenas Vencidos')
                    ->query(fn(Builder $query) => $query->whereDate('prazo_estimado', '<', now()))
                    ->toggle(), // Aparece como um switch "On/Off"
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    // ... resto dos métodos (getRelations, getPages) iguais ...
    public static function getRelations(): array
    {
        return [RelationManagers\AndamentosRelationManager::class];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcessos::route('/'),
            'create' => Pages\CreateProcesso::route('/create'),
            'edit' => Pages\EditProcesso::route('/{record}/edit'),
        ];
    }
}