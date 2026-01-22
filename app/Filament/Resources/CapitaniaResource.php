<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CapitaniaResource\Pages;
use App\Models\Capitania;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CapitaniaResource extends Resource
{
    protected static ?string $model = Capitania::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Capitanias';
    protected static ?string $navigationGroup = 'Cadastros Auxiliares';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->label('Nome da Organização Militar')
                    ->placeholder('Ex: Capitania Fluvial de Brasília')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('sigla')
                        ->label('Sigla / Indicativo')
                        ->placeholder('Ex: CFB')
                        ->required()
                        ->maxLength(20),
                    
                    Forms\Components\TextInput::make('uf')
                        ->label('UF')
                        ->length(2)
                        ->required(),

                    Forms\Components\Toggle::make('padrao')
                        ->label('Capitania Padrão?')
                        ->helperText('Se ativado, virá pré-selecionada nos anexos.')
                        ->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sigla')->searchable(),
                Tables\Columns\TextColumn::make('uf'),
                Tables\Columns\IconColumn::make('padrao')
                    ->boolean()
                    ->label('Padrão'),
            ])
            ->defaultSort('padrao', 'desc'); // Padrão aparece primeiro
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCapitanias::route('/'),
            'create' => Pages\CreateCapitania::route('/create'),
            'edit' => Pages\EditCapitania::route('/{record}/edit'),
        ];
    }
}