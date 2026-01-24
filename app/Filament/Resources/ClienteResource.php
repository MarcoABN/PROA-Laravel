<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use App\Services\ProcuracaoService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-user';

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
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cpfcnpj')
                            ->label('CPF/CNPJ')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(18),

                        Forms\Components\TextInput::make('rg')
                            ->label('RG'),

                        Forms\Components\TextInput::make('org_emissor')
                            ->label('Órgão Emissor'),

                        Forms\Components\DatePicker::make('dt_emissao')
                            ->label('Data Emissão RG'),

                        Forms\Components\DatePicker::make('data_nasc')
                            ->label('Nascimento'),

                        Forms\Components\TextInput::make('nacionalidade')
                            ->label('Nacionalidade')
                            ->placeholder('Ex: Brasileira')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('naturalidade')
                            ->label('Naturalidade')
                            ->placeholder('Ex: Goiânia - GO')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('telefone')
                            ->mask('(99) 9999-9999'),

                        Forms\Components\TextInput::make('celular')
                            ->mask('(99) 9 9999-9999'),

                        Forms\Components\TextInput::make('email')
                            ->email(),
                    ]),

                // --- SEÇÃO 2: ENDEREÇO (COM API VIACEP) ---
                Forms\Components\Section::make('Endereço')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999')
                            ->required()
                            ->live(onBlur: true)
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
                                if (!$state) return;
                                $cep = preg_replace('/[^0-9]/', '', $state);
                                if (strlen($cep) !== 8) return;

                                $response = Http::get("https://viacep.com.br/ws/{$cep}/json/")->json();

                                if (!isset($response['erro'])) {
                                    $set('logradouro', $response['logradouro'] ?? null);
                                    $set('bairro', $response['bairro'] ?? null);
                                    $set('cidade', $response['localidade'] ?? null);
                                    $set('uf', $response['uf'] ?? null);
                                }
                            }),

                        Forms\Components\TextInput::make('logradouro')
                            ->required(),

                        Forms\Components\TextInput::make('numero')
                            ->required(),

                        Forms\Components\TextInput::make('complemento'),

                        Forms\Components\TextInput::make('bairro')
                            ->required(),

                        Forms\Components\TextInput::make('cidade')
                            ->required(),

                        Forms\Components\TextInput::make('uf')
                            ->maxLength(2)
                            ->required(),
                    ]),

                // --- SEÇÃO 3: HABILITAÇÃO ---
                Forms\Components\Section::make('Carteira Habilitação (CHA)')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cha_numero')
                            ->label('Número'),
                            
                        Forms\Components\TextInput::make('cha_categoria')
                            ->label('Categoria'),
                            
                        Forms\Components\DatePicker::make('cha_dtemissao')
                            ->label('Validade/Emissão'),
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

                Tables\Columns\TextColumn::make('simulados_count')
                    ->counts('simulados')
                    ->label('Simulados')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('performance')
                    ->label('Aprov / Reprov')
                    ->state(function (Cliente $record): string {
                        $aprovados = $record->simulados()->where('aprovado', true)->count();
                        $reprovados = $record->simulados()->where('aprovado', false)->count();
                        return "{$aprovados}  /  {$reprovados}";
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('media_notas')
                    ->label('Nota Média')
                    ->state(function (Cliente $record) {
                        $media = $record->simulados()->avg('porcentagem');
                        return $media !== null ? number_format($media, 1) . '%' : '-';
                    })
                    ->alignCenter()
                    ->badge()
                    ->color(function ($state) {
                        if ($state === '-') return 'gray';
                        $valor = (float) str_replace('%', '', $state);
                        return $valor >= 50 ? 'success' : 'danger';
                    }),
            ])
            ->filters([
                // FILTRO: Status de Realização (Fez ou não fez simulados)
                SelectFilter::make('status_simulado')
                    ->label('Realizou Simulado?')
                    ->options([
                        'sim' => 'Sim, já realizou',
                        'nao' => 'Não, nunca realizou',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'sim') {
                            return $query->has('simulados');
                        }
                        if ($data['value'] === 'nao') {
                            return $query->doesntHave('simulados');
                        }
                    }),

                // FILTRO: Status de Aprovação
                SelectFilter::make('resultado')
                    ->label('Status de Aprovação')
                    ->options([
                        'aprovado' => 'Aprovados (Pelo menos um)',
                        'reprovado' => 'Reprovados (Apenas reprovados)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'aprovado') {
                            // Clientes que possuem ao menos 1 simulado aprovado
                            return $query->whereHas('simulados', fn($q) => $q->where('aprovado', true));
                        }
                        if ($data['value'] === 'reprovado') {
                            // Clientes que têm simulados, mas NENHUM deles é aprovado
                            return $query->whereHas('simulados', fn($q) => $q->where('aprovado', false))
                                         ->whereDoesntHave('simulados', fn($q) => $q->where('aprovado', true));
                        }
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // --- ACTION: PROCURAÇÃO ---
                Action::make('procuracao')
                    ->label('Procuração')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->form(function (Cliente $record) {
                        $temBarcos = $record->embarcacoes()->exists();

                        if (!$temBarcos) {
                            return [];
                        }

                        return [
                            Select::make('embarcacao_id')
                                ->label('Incluir dados da Embarcação?')
                                ->placeholder('Não incluir (Somente dados do cliente)')
                                ->options($record->embarcacoes->pluck('nome_embarcacao', 'id'))
                                ->searchable()
                                ->preload()
                                ->helperText('Selecione uma embarcação para preencher o nome e usar a cidade dela como local.'),
                        ];
                    })
                    ->action(function (Cliente $record, array $data, Action $action) {
                        $barcoId = $data['embarcacao_id'] ?? 'null';
                        
                        $url = route('clientes.procuracao', [
                            'id' => $record->id, 
                            'embarcacao_id' => $barcoId
                        ]);

                        $action->getLivewire()->js("window.open('{$url}', '_blank');");
                    }),
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