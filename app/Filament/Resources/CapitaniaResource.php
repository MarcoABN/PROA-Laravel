<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CapitaniaResource\Pages;
use App\Models\Capitania;
use App\Support\AcessoAgendamento;
use App\Support\OrganizacoesMilitaresSisap;
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

                // Oculta para quem não acessa o agendamento. Componente oculto não é hidratado,
                // então salvar a capitania não apaga os valores já gravados.
                Forms\Components\Section::make('Agendamento eletrônico (SISAP)')
                    ->visible(fn(): bool => AcessoAgendamento::permitido())
                    ->description('Usado pelos agendamentos e pela extensão do Chrome.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('sisap_nidom')
                            ->label('Código da OM no SISAP')
                            ->numeric()
                            ->live(onBlur: true)
                            ->helperText(fn($state) => OrganizacoesMilitaresSisap::nome($state)
                                ?? ($state ? 'Código fora da lista conhecida do SISAP. Confira no "?".' : 'Ex.: Capitania Fluvial de Goiás = 136.'))
                            ->hintAction(
                                Forms\Components\Actions\Action::make('listaOmsSisap')
                                    ->label('?')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->tooltip('Ver os códigos das OMs no SISAP')
                                    ->modalHeading('Códigos das OMs no SISAP')
                                    ->modalContent(view('filament.forms.lista-oms-sisap'))
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Fechar')
                            ),

                        Forms\Components\TextInput::make('sisap_vagas_por_agendamento')
                            ->label('Vagas por agendamento')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->default(3)
                            ->required()
                            ->helperText('Contador "Serviços X de N" do SISAP.'),

                        Forms\Components\TextInput::make('sisap_agendamentos_por_mes')
                            ->label('Agendamentos por procurador/mês')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->default(2)
                            ->required(),
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