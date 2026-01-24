<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados Pessoais')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('nome')->required()->columnSpanFull(),
                        Forms\Components\TextInput::make('cpfcnpj')
                            ->label('CPF/CNPJ')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(18),

                        Forms\Components\TextInput::make('rg')->label('RG'),
                        Forms\Components\TextInput::make('org_emissor')->label('Órgão Emissor'),
                        Forms\Components\DatePicker::make('dt_emissao')->label('Data Emissão RG'),
                        Forms\Components\DatePicker::make('data_nasc')->label('Nascimento'),

                        // --- ADICIONE ESTES DOIS CAMPOS AQUI ---
                        Forms\Components\TextInput::make('nacionalidade')
                            ->label('Nacionalidade')
                            ->placeholder('Ex: Brasileira')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('naturalidade')
                            ->label('Naturalidade')
                            ->placeholder('Ex: Goiânia - GO')
                            ->maxLength(255),
                        // ---------------------------------------

                        Forms\Components\TextInput::make('telefone')->mask('(99) 9999-9999'),
                        Forms\Components\TextInput::make('celular')->mask('(99) 9 9999-9999'),
                        Forms\Components\TextInput::make('email')->email(),
                    ]),

                Forms\Components\Section::make('Endereço')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->mask('99999-999')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state)
                                    return;
                                $cep = preg_replace('/[^0-9]/', '', $state);
                                if (strlen($cep) !== 8)
                                    return;
                                $response = Http::get("https://viacep.com.br/ws/{$cep}/json/")->json();
                                if (!isset($response['erro'])) {
                                    $set('logradouro', $response['logradouro'] ?? null);
                                    $set('bairro', $response['bairro'] ?? null);
                                    $set('cidade', $response['localidade'] ?? null);
                                    $set('uf', $response['uf'] ?? null);
                                }
                            }),

                        Forms\Components\TextInput::make('logradouro')->required(),
                        Forms\Components\TextInput::make('numero')->required(),
                        Forms\Components\TextInput::make('complemento'),
                        Forms\Components\TextInput::make('bairro')->required(),
                        Forms\Components\TextInput::make('cidade')->required(),
                        Forms\Components\TextInput::make('uf')->maxLength(2)->required(),
                    ]),

                Forms\Components\Section::make('Carteira Habilitação (CHA)')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cha_numero')->label('Número'),
                        Forms\Components\TextInput::make('cha_categoria')->label('Categoria'),
                        Forms\Components\DatePicker::make('cha_dtemissao')->label('Validade/Emissão'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cpfcnpj')
                    ->label('Documento')
                    ->searchable(),

                // 1. Total de Rodadas (Usa o relacionamento criado no passo 1)
                Tables\Columns\TextColumn::make('simulados_count')
                    ->counts('simulados')
                    ->label('Simulados')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                // 2. Aprovados vs Reprovados (Cálculo via Query)
                Tables\Columns\TextColumn::make('performance')
                    ->label('Aprov / Reprov')
                    ->state(function (Cliente $record): string {
                        // Conta direto no banco de dados para ser rápido
                        $aprovados = $record->simulados()->where('aprovado', true)->count();
                        $reprovados = $record->simulados()->where('aprovado', false)->count();

                        return "{$aprovados}  /  {$reprovados}";
                    })
                    ->alignCenter(),

                // 3. Nota Média (Cálculo da Média)
                Tables\Columns\TextColumn::make('media_notas')
                    ->label('Nota Média')
                    ->state(function (Cliente $record) {
                        $media = $record->simulados()->avg('porcentagem');
                        return $media !== null ? number_format($media, 1) . '%' : '-';
                    })
                    ->alignCenter()
                    ->badge()
                    ->color(function ($state) {
                        if ($state === '-')
                            return 'gray';
                        // Remove o % para comparar
                        $valor = (float) str_replace('%', '', $state);
                        return $valor >= 50 ? 'success' : 'danger';
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}