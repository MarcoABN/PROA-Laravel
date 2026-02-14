<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EscolaNauticaResource\Pages;
use App\Models\EscolaNautica;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;

class EscolaNauticaResource extends Resource
{
    protected static ?string $model = EscolaNautica::class;

    protected static ?string $navigationGroup = 'Cadastros Auxiliares';
    protected static ?string $modelLabel = 'Escola Náutica';
    protected static ?string $pluralModelLabel = 'Escolas Náuticas';
    protected static ?string $navigationLabel = 'Escolas Náuticas';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SEÇÃO 1: Identificação ---
                Forms\Components\Section::make('Identificação da Escola')
                    ->description('Dados principais e logomarca da instituição.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logomarca')
                            ->image()
                            ->directory('logos-escolas')
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull()
                            ->imageEditor(),

                        Forms\Components\TextInput::make('razao_social')
                            ->label('Razão Social')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->mask('99.999.999/9999-99')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),
                    ]),

                // --- SEÇÃO 2: Endereço e Contato (Completo) ---
                Forms\Components\Section::make('Localização e Contato')
                    ->description('Endereço completo e meios de contato.')
                    ->columns(3)
                    ->schema([
                        // Linha 1: CEP e Logradouro
                        Forms\Components\TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999')
                            ->placeholder('00000-000'),
                        
                        Forms\Components\TextInput::make('logradouro')
                            ->label('Logradouro')
                            ->placeholder('Rua, Avenida, etc.')
                            ->columnSpan(2),

                        // Linha 2: Número, Compl, Bairro
                        Forms\Components\TextInput::make('numero')
                            ->label('Número'),
                        
                        Forms\Components\TextInput::make('complemento')
                            ->label('Complemento'),
                        
                        Forms\Components\TextInput::make('bairro')
                            ->label('Bairro'),

                        // Linha 3: Cidade e UF
                        Forms\Components\TextInput::make('cidade')
                            ->label('Cidade')
                            ->required(),

                        Forms\Components\TextInput::make('uf')
                            ->label('UF')
                            ->maxLength(2)
                            ->required(),

                        // Linha 4: Contatos Digitais
                        Forms\Components\TextInput::make('site')
                            ->label('Website')
                            ->url()
                            ->prefix('https://')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('email_contato')
                            ->label('E-mail Geral')
                            ->email()
                            ->columnSpan(2),

                        // Linha 5: Telefones
                        Forms\Components\TextInput::make('telefone_responsavel')
                            ->label('Telefone Principal')
                            ->mask('(99) 99999-9999')
                            ->placeholder('(99) 99999-9999'),
                            
                        Forms\Components\TextInput::make('telefone_secundario')
                            ->label('Telefone Secundário')
                            ->mask('(99) 99999-9999'),
                    ]),

                // --- SEÇÃO 3: Vínculos (Prestadores) ---
                Forms\Components\Section::make('Responsáveis e Instrutores')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('responsavel_id')
                            ->label('Responsável Legal')
                            ->relationship('responsavel', 'nome')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nome')->required(),
                                Forms\Components\TextInput::make('cpf')->mask('999.999.999-99'),
                            ])
                            ->required(),

                        Forms\Components\Select::make('instrutor_id')
                            ->label('Instrutor Padrão')
                            ->relationship('instrutor', 'nome')
                            // Filtro opcional caso queira mostrar apenas quem é instrutor
                            // ->relationship('instrutor', 'nome', modifyQueryUsing: fn($query) => $query->where('is_instrutor', true))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                // --- SEÇÃO 4: Dados Bancários ---
                Forms\Components\Section::make('Dados Bancários')
                    ->collapsed() // Vem fechado por padrão para economizar espaço visual
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('banco')
                            ->label('Banco')
                            ->placeholder('Ex: Banco do Brasil'),
                        Forms\Components\TextInput::make('agencia')
                            ->label('Agência'),
                        Forms\Components\TextInput::make('conta_corrente')
                            ->label('Conta Corrente'),
                        Forms\Components\TextInput::make('chave_pix')
                            ->label('Chave PIX'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->circular(),
                
                TextColumn::make('razao_social')
                    ->label('Escola')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable(),

                TextColumn::make('cidade')
                    ->label('Localização')
                    ->formatStateUsing(fn ($record) => "{$record->cidade} - {$record->uf}")
                    ->sortable(),

                TextColumn::make('responsavel.nome')
                    ->label('Responsável')
                    ->searchable(),
                
                TextColumn::make('telefone_responsavel')
                    ->label('Contato'),
            ])
            ->filters([
                // Filtros podem ser adicionados aqui futuramente (ex: por UF)
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
            // Relações (RelationManagers) podem ser adicionadas aqui
        ];
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