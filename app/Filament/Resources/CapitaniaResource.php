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
                Forms\Components\Section::make('Identificação')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome da Organização Militar')
                            ->placeholder('Ex: Capitania Fluvial de Brasília')
                            ->required()
                            ->columnSpanFull(),
                        
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
                                ->label('Padrão')
                                ->columnSpan(1),
                        ]),
                    ]),

                // --- ADICIONE ESTA SEÇÃO DE COMANDO ---
                Forms\Components\Section::make('Comando (Para Ofícios)')
                    ->description('Dados utilizados no cabeçalho dos documentos.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('capitao_nome')
                            ->label('Nome do Capitão'),
                        Forms\Components\TextInput::make('capitao_patente')
                            ->label('Patente/Função')
                            ->placeholder('Ex: Capitão de Mar e Guerra'),
                    ]),

                // --- ADICIONE ESTA SEÇÃO DE ENDEREÇO ---
                Forms\Components\Section::make('Endereço')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999'),
                        
                        Forms\Components\TextInput::make('logradouro')
                            ->columnSpan(2),
                            
                        Forms\Components\TextInput::make('numero')
                            ->label('Número'),
                            
                        Forms\Components\TextInput::make('complemento'),
                        
                        Forms\Components\TextInput::make('bairro'),
                        
                        Forms\Components\TextInput::make('cidade')
                            ->required(),
                            
                        // UF já foi pedido lá em cima, não precisa repetir se não quiser
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sigla')->searchable(),
                Tables\Columns\TextColumn::make('cidade')->label('Cidade'), // Agora vai funcionar
                Tables\Columns\IconColumn::make('padrao')->boolean()->label('Padrão'),
            ])
            ->defaultSort('padrao', 'desc');
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