<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrestadorResource\Pages;
use App\Models\Prestador;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PrestadorResource extends Resource
{
    protected static ?string $model = Prestador::class;

    protected static ?string $navigationGroup = 'Cadastros Auxiliares';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Instrutores / Procuradores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SEÇÃO 1: DADOS PESSOAIS ---
                Forms\Components\Section::make('Dados Pessoais')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cpfcnpj')
                            ->label('CPF')
                            ->mask('999.999.999-99')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        // Grupo de RG
                        Forms\Components\Fieldset::make('Documento de Identidade (RG)')
                            ->schema([
                                Forms\Components\TextInput::make('rg')
                                    ->label('Número RG')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('org_emissor')
                                    ->label('Org. Emissor'),
                                Forms\Components\DatePicker::make('dt_emissao')
                                    ->label('Data de Emissão')
                                    ->displayFormat('d/m/Y') // <--- Visual (Dia/Mês/Ano)
                                    ->format('Y-m-d')        // <--- Banco (Ano-Mês-Dia)
                            ])->columns(3),

                        // Outros Dados Civis
                        Forms\Components\TextInput::make('nacionalidade'),
                        Forms\Components\TextInput::make('estado_civil'),
                        Forms\Components\TextInput::make('profissao'),

                        // Contatos
                        Forms\Components\TextInput::make('telefone')
                            ->label('Telefone Fixo')
                            ->mask('(99) 9999-9999'),
                        Forms\Components\TextInput::make('celular')
                            ->label('Celular / WhatsApp')
                            ->mask('(99) 99999-9999'),
                    ]),

                // --- SEÇÃO 2: ENDEREÇO ---
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
                        Forms\Components\TextInput::make('cidade'),
                        Forms\Components\TextInput::make('uf')
                            ->label('UF')
                            ->maxLength(2),
                    ])->collapsible(),

                // --- SEÇÃO 3: DADOS DA HABILITAÇÃO (CHA) ---
                Forms\Components\Section::make('Dados da Habilitação (CHA)')
                    ->description('Necessário para emissão de Atestados (Anexos 3B e 5E).')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('cha_numero')
                            ->label('Número da CHA')
                            ->maxLength(20),

                        Forms\Components\Select::make('cha_categoria')
                            ->label('Categoria')
                            ->options([
                                'ARA' => 'Arrais-Amador (ARA)',
                                'MTA' => 'Motonauta (MTA)',
                                'ARA-MTA' => 'Arrais e Motonauta (ARA-MTA)',
                                'MSA' => 'Mestre-Amador (MSA)',
                                'CPA' => 'Capitão-Amador (CPA)',
                                'VELEIRO' => 'Veleiro',
                            ])
                            ->searchable(),

                        Forms\Components\DatePicker::make('cha_dtemissao')
                            ->label('Data de Emissão CHA')
                            ->displayFormat('d/m/Y'),
                    ]),

                // --- SEÇÃO 4: FUNÇÕES E VÍNCULOS ---
                Forms\Components\Section::make('Funções do Prestador')
                    ->description('Defina se este cadastro pode ser usado como Instrutor ou Procurador no sistema.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_instrutor')
                            ->label('É Instrutor?')
                            ->default(false)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_procurador')
                            ->label('É Procurador?')
                            ->default(false)
                            ->live(), // Recarrega o formulário para mostrar o tipo

                        Forms\Components\Select::make('tipo_procuracao')
                            ->label('Tipo de Procuração')
                            ->options([
                                'COMPLETO' => 'Completo',
                                'REDUZIDO' => 'Reduzido'
                            ])
                            ->visible(fn(Get $get) => $get('is_procurador')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cpfcnpj')
                    ->label('Documento'),
                Tables\Columns\TextColumn::make('celular')
                    ->label('Contato'),
                Tables\Columns\IconColumn::make('is_instrutor')
                    ->boolean()
                    ->label('Instrutor'),
                Tables\Columns\IconColumn::make('is_procurador')
                    ->boolean()
                    ->label('Procurador'),
                Tables\Columns\TextColumn::make('cidade')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('instrutores')
                    ->query(fn($query) => $query->where('is_instrutor', true))
                    ->label('Apenas Instrutores'),
                Tables\Filters\Filter::make('procuradores')
                    ->query(fn($query) => $query->where('is_procurador', true))
                    ->label('Apenas Procuradores'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrestadors::route('/'),
            'create' => Pages\CreatePrestador::route('/create'),
            'edit' => Pages\EditPrestador::route('/{record}/edit'),
        ];
    }
}