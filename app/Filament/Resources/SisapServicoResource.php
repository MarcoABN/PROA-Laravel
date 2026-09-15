<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SisapServicoResource\Pages;
use App\Models\SisapServico;
use App\Support\AcessoAgendamento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SisapServicoResource extends Resource
{
    protected static ?string $model = SisapServico::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Cadastros Auxiliares';
    protected static ?string $navigationLabel = 'Serviços do SISAP';
    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'sisap-servicos';
    protected static ?string $modelLabel = 'Serviço do SISAP';
    protected static ?string $pluralModelLabel = 'Serviços do SISAP';
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
            ->schema([
                Forms\Components\Section::make('Serviço')
                    ->schema([
                        Forms\Components\TextInput::make('sigla')
                            ->label('Sigla')
                            ->required()
                            ->maxLength(50)
                            ->helperText('Como aparece nas listas de agendamento. Ex.: INSC EMB, RNV EMB, TRANSF EMB.'),

                        Forms\Components\Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true),

                        Forms\Components\Textarea::make('descricao_sisap')
                            ->label('Opção no SISAP')
                            ->required()
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Copie o texto exato da opção exibida no SISAP depois de informar a GRU. A extensão escolhe o serviço por este texto.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sigla')
            ->columns([
                Tables\Columns\TextColumn::make('sigla')
                    ->label('Sigla')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('descricao_sisap')
                    ->label('Opção no SISAP')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('solicitacoes_count')
                    ->label('Solicitações')
                    ->counts('solicitacoes')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ativo')->label('Ativo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSisapServicos::route('/'),
            'create' => Pages\CreateSisapServico::route('/create'),
            'edit' => Pages\EditSisapServico::route('/{record}/edit'),
        ];
    }
}
