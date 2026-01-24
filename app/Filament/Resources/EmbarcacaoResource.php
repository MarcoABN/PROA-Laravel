<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmbarcacaoResource\Pages;
use App\Models\Embarcacao;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString; // Importante para o ícone de loading
use Illuminate\Support\Facades\Http; // Importante para a API do ViaCEP

class EmbarcacaoResource extends Resource
{
    protected static ?string $model = Embarcacao::class;

    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationLabel = 'Embarcações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SEÇÃO 1: VÍNCULO COM CLIENTE ---
                Forms\Components\Section::make('Proprietário')
                    ->schema([
                        Forms\Components\Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                // Ao selecionar cliente, copia o endereço dele para a embarcação
                                if ($cliente = Cliente::find($state)) {
                                    $set('cep', $cliente->cep);
                                    $set('logradouro', $cliente->logradouro);
                                    $set('numero', $cliente->numero);
                                    $set('bairro', $cliente->bairro);
                                    $set('cidade', $cliente->cidade);
                                    $set('uf', $cliente->uf);
                                    $set('complemento', $cliente->complemento);
                                }
                            }),
                    ]),

                // --- SEÇÃO 2: DADOS DA EMBARCAÇÃO ---
                Forms\Components\Section::make('Detalhes da Embarcação')
                    ->columns(2)
                    ->schema([
                        // CORREÇÃO 1: Removido required e adicionado placeholder
                        Forms\Components\TextInput::make('nome_embarcacao')
                            ->label('Nome da Embarcação')
                            ->placeholder('Caso não tenha nome, deixe em branco'),

                        Forms\Components\TextInput::make('num_casco')->label('Número do Casco'),
                        Forms\Components\TextInput::make('num_inscricao')->label('Inscrição'),

                        Forms\Components\Select::make('tipo_embarcacao')
                            ->options([
                                'MOTOAQUÁTICA' => 'MOTOAQUÁTICA',
                                'LANCHA' => 'LANCHA',
                                'VELEIRO' => 'VELEIRO',
                                'IATE' => 'IATE',
                                'CATAMARÃ' => 'CATAMARÃ',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('tipo_atividade')
                            ->options(['ESPORTE LAZER' => 'ESPORTE LAZER', 'COMERCIAL' => 'COMERCIAL'])
                            ->required(),

                        Forms\Components\Select::make('area_navegacao')
                            ->options(['INTERIOR' => 'INTERIOR', 'MAR ABERTO' => 'MAR ABERTO'])
                            ->required(),

                        Forms\Components\DatePicker::make('dt_construcao'),
                        Forms\Components\DatePicker::make('dt_inscricao'),
                        Forms\Components\TextInput::make('valor')->numeric()->prefix('R$'),
                    ]),

                // --- SEÇÃO 3: CARACTERÍSTICAS TÉCNICAS ---
                Forms\Components\Section::make('Características e Medidas')
                    ->collapsible() // CORREÇÃO 2: Permite fechar, mas começa aberto
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('comp_total')->numeric()->label('Comp. Total'),
                        Forms\Components\TextInput::make('boca_moldada')->numeric(),
                        Forms\Components\TextInput::make('pontal_moldado')->numeric(),
                        Forms\Components\TextInput::make('calado')->numeric(),
                        Forms\Components\TextInput::make('arqueacao_bruta')->numeric(),
                        Forms\Components\TextInput::make('mat_casco')->label('Material do Casco'),
                        Forms\Components\TextInput::make('qtd_tripulantes')->numeric(),
                        Forms\Components\TextInput::make('lotacao')->numeric(),
                        Forms\Components\TextInput::make('potencia_motor')
                            ->label('Potência Máx. (HP)')
                            ->numeric()
                            ->suffix('HP'),
                    ]),

                // --- SEÇÃO 4: ENDEREÇO ONDE FICA A EMBARCAÇÃO ---
                Forms\Components\Section::make('Localização da Embarcação')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->mask('99999-999')
                            ->live(onBlur: true)
                            // CORREÇÃO 3: Visual de Loading igual ao ClienteResource
                            ->helperText(new HtmlString('
                                <div wire:loading wire:target="data.cep" class="text-primary-500 text-sm font-bold flex items-center gap-2 mt-1">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Buscando endereço...
                                </div>
                            '))
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state)
                                    return;

                                // Remove formatação para enviar para a API
                                $cep = preg_replace('/[^0-9]/', '', $state);

                                if (strlen($cep) !== 8)
                                    return;

                                $response = Http::get("https://viacep.com.br/ws/{$cep}/json/")->json();

                                if (!isset($response['erro'])) {
                                    $set('logradouro', $response['logradouro'] ?? null);
                                    $set('bairro', $response['bairro'] ?? null);
                                    $set('cidade', $response['localidade'] ?? null);
                                    $set('uf', $response['uf'] ?? null);
                                    $set('complemento', $response['complemento'] ?? null);
                                }
                            }),

                        Forms\Components\TextInput::make('logradouro'),
                        Forms\Components\TextInput::make('numero'),
                        Forms\Components\TextInput::make('bairro'),
                        Forms\Components\TextInput::make('cidade'),
                        Forms\Components\TextInput::make('uf'),
                    ]),

                // --- SEÇÃO 5: MOTORES ---
                Forms\Components\Section::make('Motorização')
                    ->schema([
                        Forms\Components\Repeater::make('motores')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('marca')->required(),
                                Forms\Components\TextInput::make('num_serie')->required()->label('Nº Série'),
                                Forms\Components\TextInput::make('potencia')->numeric()->label('Potência (HP)'),
                            ])
                            ->columns(3)
                            ->addActionLabel('Adicionar Motor')
                            ->defaultItems(0), // CORREÇÃO 4: Removemos o optional() que causava erro
                    ]),

                // --- SEÇÃO 6: NOTA FISCAL ---
                Forms\Components\Section::make('Nota Fiscal')
                    ->relationship('notaFiscal')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('cnpj_vendedor')->label('CNPJ Vendedor')->mask('99.999.999/9999-99'),
                                Forms\Components\TextInput::make('razao_social')->label('Razão Social'),
                                Forms\Components\TextInput::make('numero_nota')->label('Número da Nota'),
                                Forms\Components\DatePicker::make('dt_venda')->label('Data da Venda'),
                            ]),

                        Forms\Components\FileUpload::make('pdf_path')
                            ->label('Arquivo PDF')
                            ->disk('public')
                            ->directory('notas-fiscais')
                            ->acceptedFileTypes(['application/pdf'])
                            ->downloadable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome_embarcacao')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Nome não informado'), // Visual na tabela quando vazio

                Tables\Columns\TextColumn::make('cliente.nome')
                    ->label('Proprietário')
                    ->searchable(query: function ($query, string $search) {
                        // Limpa pontuação para pesquisar CPF mesmo se o usuário digitar pontos
                        $searchNumeros = preg_replace('/[^0-9]/', '', $search);

                        // Busca dentro do relacionamento 'cliente'
                        $query->whereHas('cliente', function ($q) use ($search, $searchNumeros) {
                            $q->where('nome', 'ilike', "%{$search}%") // Busca por Nome (Insensitive)
                                ->orWhere('cpfcnpj', 'like', "%{$search}%") // Busca CPF exato digitado
                                ->orWhere('cpfcnpj', 'like', "%{$searchNumeros}%"); // Busca CPF apenas números
                        });
                    }),
                Tables\Columns\TextColumn::make('tipo_embarcacao'),
                Tables\Columns\TextColumn::make('num_inscricao'),
                Tables\Columns\TextColumn::make('motores_count')->counts('motores')->label('Qtd Motores'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmbarcacaos::route('/'),
            'create' => Pages\CreateEmbarcacao::route('/create'),
            'edit' => Pages\EditEmbarcacao::route('/{record}/edit'),
        ];
    }
}