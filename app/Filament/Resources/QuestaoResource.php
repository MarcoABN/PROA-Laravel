<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestaoResource\Pages;
use App\Filament\Resources\QuestaoResource\RelationManagers;
use App\Models\Questao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestaoResource extends Resource
{
    protected static ?string $model = Questao::class;

    protected static ?string $navigationLabel = 'Questões do Simulado';

    protected static ?string $navigationGroup = 'Painel de Controle';

    protected static ?string $slug = 'questoes-do-simulado';

    protected static ?string $modelLabel = 'Questão do Simulado';

    protected static ?string $pluralModelLabel = 'Questões do Simulado';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\TextInput::make('categoria')->disabled(),
                        Forms\Components\TextInput::make('assunto')->disabled(),
                        Forms\Components\Textarea::make('enunciado')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Alternativas')
                    ->description('A alternativa correta está destacada no banco')
                    ->schema([
                        Forms\Components\TextInput::make('alternativa_a')->required(),
                        Forms\Components\TextInput::make('alternativa_b')->required(),
                        Forms\Components\TextInput::make('alternativa_c')->required(),
                        Forms\Components\TextInput::make('alternativa_d')->required(),
                        Forms\Components\TextInput::make('alternativa_e'),
                        Forms\Components\Select::make('resposta_correta')
                            ->options([
                                'a' => 'A',
                                'b' => 'B',
                                'c' => 'C',
                                'd' => 'D',
                                'e' => 'E',
                            ])->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('enunciado')
                    ->limit(50) // Mostra apenas o começo para não quebrar o layout
                    ->searchable(),
                Tables\Columns\TextColumn::make('resposta_correta')
                    ->badge() // Transforma a letra em um balão colorido
                    ->color('success')
                    ->label('Gabarito'),
                Tables\Columns\IconColumn::make('ativo')
                    ->boolean(),
            ])
            ->filters([
                // Você pode adicionar filtros por assunto aqui futuramente
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestaos::route('/'),
            'create' => Pages\CreateQuestao::route('/create'),
            'edit' => Pages\EditQuestao::route('/{record}/edit'),
        ];
    }
}
