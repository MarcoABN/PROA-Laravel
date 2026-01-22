<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropostaResource\Pages;
use App\Models\Proposta;
use App\Services\PropostaDocService;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Closure;

class PropostaResource extends Resource
{
    protected static ?string $model = Proposta::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Propostas de Serviço';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Cabeçalho da Proposta')
                    ->columns(3)
                    ->schema([
                        Select::make('escola_nautica_id')
                            ->label('Escola Náutica (Proponente)')
                            ->relationship('escola', 'razao_social')
                            ->required()
                            ->default(fn() => \App\Models\EscolaNautica::first()->id ?? null),

                        DatePicker::make('data_proposta')
                            ->label('Data de Emissão')
                            ->default(now())
                            ->required(),

                        TextInput::make('sequencial_diario')
                            ->label('Nº Sequencial')
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ]),

                Section::make('Cliente e Embarcação')
                    ->columns(2)
                    ->schema([
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nome')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('embarcacao_id', null))
                            ->required(),

                        Select::make('embarcacao_id')
                            ->label('Embarcação (Objeto)')
                            ->options(function (Get $get) {
                                $clienteId = $get('cliente_id');
                                if (!$clienteId) return [];
                                return \App\Models\Embarcacao::where('cliente_id', $clienteId)
                                    ->pluck('nome_embarcacao', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Section::make('Serviços e Valores')
                    ->schema([
                        Repeater::make('itens_servico')
                            ->label('Lista de Serviços')
                            ->schema([
                                TextInput::make('descricao')
                                    ->label('Descrição do Serviço')
                                    ->required()
                                    ->columnSpan(2),
                                    
                                TextInput::make('valor')
                                    ->label('Valor')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->live(onBlur: true)
                                    ->required(),
                            ])
                            ->maxItems(11)
                            ->columns(3)
                            ->live(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('valor_desconto')
                                    ->label('Desconto')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0)
                                    ->live(onBlur: true),

                                Placeholder::make('total_calculado')
                                    ->label('Total Líquido Estimado')
                                    ->content(function (Get $get) {
                                        $total = self::getTotalLiquido($get);
                                        return 'R$ ' . number_format($total, 2, ',', '.');
                                    })
                                    ->extraAttributes(['class' => 'text-xl font-bold text-success-600']),
                            ]),
                    ]),

                // --- LÓGICA DE PAGAMENTO CORRIGIDA ---
                Section::make('Condições de Pagamento')
                    ->schema([
                        // CORREÇÃO 1: dehydrated(false) impede o erro SQL
                        Hidden::make('parcelas_count')
                            ->default(1)
                            ->dehydrated(false), 

                        Repeater::make('parcelas')
                            ->label('Parcelamento')
                            ->schema([
                                TextInput::make('descricao')
                                    ->label('Descrição')
                                    ->placeholder('Ex: Entrada / 30 dias')
                                    ->required(),

                                TextInput::make('valor')
                                    ->label('Valor (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->live(onBlur: true) 
                                    // Passamos o $state explicitamente
                                    ->afterStateUpdated(function (Get $get, Set $set, $component, $state) {
                                        self::recalcularCascata($get, $set, $component, $state);
                                    })
                                    ->required(),
                            ])
                            ->maxItems(4)
                            ->defaultItems(1)
                            ->columns(2)
                            ->addActionLabel('Adicionar Parcela')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $novaQtd = count($state);
                                $qtdAnterior = (int) $get('parcelas_count');

                                // Só divide igualmente se mudou a quantidade de linhas
                                if ($novaQtd !== $qtdAnterior) {
                                    self::recalcularDivisaoIgual($get, $set);
                                    $set('parcelas_count', $novaQtd);
                                }
                            })
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $totalLiquido = self::getTotalLiquido($get);
                                    
                                    // Converte para float antes de somar para evitar erro de string
                                    $totalParcelas = collect($value)->sum(fn($p) => (float) ($p['valor'] ?? 0));
                                    $totalParcelas = round($totalParcelas, 2);

                                    if (abs($totalLiquido - $totalParcelas) > 0.05) {
                                        $diferenca = number_format($totalLiquido - $totalParcelas, 2, ',', '.');
                                        $fail("A soma das parcelas (R$ " . number_format($totalParcelas, 2, ',', '.') . ") difere do total líquido (R$ " . number_format($totalLiquido, 2, ',', '.') . ").");
                                    }
                                },
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_formatado')->label('Nº Proposta')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cliente.nome')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('data_proposta')->date('d/m/Y')->label('Data'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'rascunho' => 'gray',
                        'gerada' => 'warning',
                        'aceita' => 'success',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('imprimir')
                    ->label('Imprimir PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (Proposta $record) => route('propostas.imprimir', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPropostas::route('/'),
            'create' => Pages\CreateProposta::route('/create'),
            'edit' => Pages\EditProposta::route('/{record}/edit'),
        ];
    }

    // --- MÉTODOS AUXILIARES ---

    protected static function getTotalLiquido(Get $get): float
    {
        $itens = $get('itens_servico') ?? [];
        $totalServico = collect($itens)->sum(fn($item) => (float) ($item['valor'] ?? 0));
        $desconto = (float) ($get('valor_desconto') ?? 0);
        return round($totalServico - $desconto, 2);
    }

    public static function recalcularCascata(Get $get, Set $set, $component, $novoValorState)
    {
        $totalLiquido = self::getTotalLiquido($get);
        $parcelas = $get('parcelas') ?? [];

        // Identifica o UUID da linha atual
        $uuidEditado = Str::afterLast(Str::beforeLast($component->getStatePath(), '.'), '.');

        $somaAnterior = 0;
        $encontrouAtual = false;
        $chavesFuturas = [];

        // Itera para separar: Passado / Presente / Futuro
        foreach ($parcelas as $uuid => $dados) {
            // Se já passamos pela linha editada, é Futuro
            if ($encontrouAtual) {
                $chavesFuturas[] = $uuid;
                continue;
            }

            // Se for a linha atual
            if ((string)$uuid === (string)$uuidEditado) {
                $valor = (float) $novoValorState; // Usa o valor que acabou de ser digitado
                $somaAnterior += $valor;
                $encontrouAtual = true;
            } else {
                // Linhas anteriores
                $somaAnterior += (float) ($dados['valor'] ?? 0);
            }
        }

        // Se não houver linhas futuras, não há o que recalcular
        if (empty($chavesFuturas)) return;

        // Calcula o que sobra para as próximas
        $restante = round($totalLiquido - $somaAnterior, 2);
        $qtdFuturas = count($chavesFuturas);

        // Distribui o restante
        $valorBase = floor(($restante / $qtdFuturas) * 100) / 100;
        $somaDistrib = $valorBase * $qtdFuturas;
        $centavosSobra = round($restante - $somaDistrib, 2);

        foreach ($chavesFuturas as $index => $uuid) {
            $novoValor = $valorBase;
            if ($index === ($qtdFuturas - 1)) {
                $novoValor += $centavosSobra;
            }
            
            // Atualiza o valor no array
            // Formatamos para string para garantir que o input exiba "100.00"
            $parcelas[$uuid]['valor'] = number_format($novoValor, 2, '.', '');
        }

        $set('parcelas', $parcelas);
    }

    public static function recalcularDivisaoIgual(Get $get, Set $set)
    {
        $totalLiquido = self::getTotalLiquido($get);
        $parcelas = $get('parcelas');

        if (!$parcelas || count($parcelas) == 0) return;

        $qtd = count($parcelas);
        $valorBase = floor(($totalLiquido / $qtd) * 100) / 100;
        $soma = $valorBase * $qtd;
        $diferenca = round($totalLiquido - $soma, 2);

        $i = 0;
        foreach ($parcelas as $uuid => $dados) {
            $novoValor = $valorBase;
            if ($i === ($qtd - 1)) {
                $novoValor += $diferenca;
            }
            $parcelas[$uuid]['valor'] = number_format($novoValor, 2, '.', '');
            $i++;
        }

        $set('parcelas', $parcelas);
    }
}