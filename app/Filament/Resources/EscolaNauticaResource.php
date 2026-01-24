<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EscolaNauticaResource\Pages;
use App\Models\EscolaNautica;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;

class EscolaNauticaResource extends Resource
{
    protected static ?string $model = EscolaNautica::class;

    protected static ?string $navigationGroup = 'Cadastros Auxiliares';

    protected static ?string $modelLabel = 'Escolas Náuticas';
    protected static ?string $pluralModelLabel = 'Escolas Náuticas';
    protected static ?string $navigationLabel = 'Escolas Náuticas';
    
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap'; // Ícone sugerido

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados Principais')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('razao_social')
                            ->label('Razão Social')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->mask('99.999.999/9999-99')
                            ->required()
                            ->unique(ignoreRecord: true),

                        FileUpload::make('logo_path')
                            ->label('Logomarca')
                            ->image()
                            ->directory('logos-escolas')
                            ->disk('public')
                            ->visibility('public'),
                    ]),

                // --- NOVOS CAMPOS ADICIONADOS AQUI ---
                Forms\Components\Section::make('Endereço e Contato')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cidade')
                            ->label('Cidade')
                            ->required(),

                        Forms\Components\TextInput::make('uf')
                            ->label('UF')
                            ->maxLength(2)
                            ->required(),

                        Forms\Components\TextInput::make('site')
                            ->label('Site')
                            ->url()
                            ->prefix('https://'),

                        Forms\Components\TextInput::make('email_contato')
                            ->label('E-mail de Contato')
                            ->email(),

                        Forms\Components\TextInput::make('telefone_responsavel')
                            ->label('Telefone do Responsável')
                            ->mask('(99) 99999-9999'),
                            
                        Forms\Components\TextInput::make('telefone_secundario')
                            ->label('Telefone Secundário')
                            ->mask('(99) 99999-9999'),
                    ]),
                // -------------------------------------

                Forms\Components\Section::make('Vínculos')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('instrutor_id')
                            ->label('Instrutor')
                            ->relationship('instrutor', 'nome', modifyQueryUsing: fn($query) => $query->where('is_instrutor', true))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('responsavel_id')
                            ->label('Responsável')
                            ->relationship('responsavel', 'nome')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                // ... dentro do schema do form ...

                Forms\Components\Section::make('Dados Bancários')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('banco')
                            ->label('Instituição Bancária')
                            ->placeholder('Ex: Banco do Brasil')
                            ->required(),
                        Forms\Components\TextInput::make('agencia')
                            ->label('Agência')
                            ->required(),
                        Forms\Components\TextInput::make('conta_corrente')
                            ->label('Conta Corrente')
                            ->required(),
                        Forms\Components\TextInput::make('chave_pix')
                            ->label('Chave PIX'),
                    ]),

                // ...
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')->label('Logo')->circular(),
                Tables\Columns\TextColumn::make('razao_social')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cnpj'),
                Tables\Columns\TextColumn::make('cidade')->label('Cidade'), // Adicionado na tabela
                Tables\Columns\TextColumn::make('responsavel.nome')->label('Responsável'),
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

    // ... resto do arquivo (getRelations, getPages) mantém igual
    public static function getRelations(): array
    {
        return [];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEscolaNauticas::route('/'),
            'create' => Pages\CreateEscolaNautica::route('/create'),
            'edit' => Pages\EditEscolaNautica::route('/{record}/edit'),
        ];
    }
}