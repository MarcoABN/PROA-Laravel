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
                                    ->label('Título/Identificação')
                                    ->required(),

                                Forms\Components\Select::make('tipo_servico')
                                    ->label('Tipo de Serviço')
                                    ->options([
                                        Processo::TIPO_CHA => Processo::TIPO_CHA,
                                        Processo::TIPO_TIE => Processo::TIPO_TIE,
                                        Processo::TIPO_MOTO => Processo::TIPO_MOTO,
                                        Processo::TIPO_DEFESA => Processo::TIPO_DEFESA,
                                        Processo::TIPO_OUTROS => Processo::TIPO_OUTROS,
                                    ])
                                    ->required(),

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
                                        if (!$clienteId) return [];
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
                                    ->required(),

                                Forms\Components\Select::make('prioridade')
                                    ->options([
                                        'baixa' => 'Baixa',
                                        'normal' => 'Normal',
                                        'alta' => 'Alta',
                                        'urgente' => 'Urgente',
                                    ])
                                    ->default('normal')
                                    ->required(),

                                Forms\Components\DatePicker::make('prazo_estimado')
                                    ->label('Prazo Legal/Estimado')
                                    ->displayFormat('d/m/Y')
                                    ->native(false),

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
                Tables\Columns\TextColumn::make('tipo_servico')
                    ->label('Serviço / Cliente')
                    ->searchable()
                    ->weight('bold')
                    ->formatStateUsing(fn (Processo $record) => "{$record->tipo_servico} - {$record->cliente->nome}")
                    ->description(fn (Processo $record) => $record->titulo),

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
                    ->color(fn(Processo $record) => ($record->prazo_estimado && $record->prazo_estimado < now()->startOfDay() && !in_array($record->status, ['concluido', 'arquivado'])) ? 'danger' : 'gray')
                    ->description(fn(Processo $record) => $record->prazo_estimado?->diffForHumans()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_servico')
                    ->label('Tipo de Serviço')
                    ->options([
                        Processo::TIPO_CHA => Processo::TIPO_CHA,
                        Processo::TIPO_TIE => Processo::TIPO_TIE,
                        Processo::TIPO_MOTO => Processo::TIPO_MOTO,
                        Processo::TIPO_DEFESA => Processo::TIPO_DEFESA,
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'triagem' => 'Triagem',
                        'analise' => 'Em Análise',
                        'aguardando_cliente' => 'Aguardando Cliente',
                        'protocolado' => 'Protocolado',
                        'exigencia' => 'Com Exigência',
                        'concluido' => 'Concluído',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

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