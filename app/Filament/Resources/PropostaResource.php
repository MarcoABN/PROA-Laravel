<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropostaResource\Pages;
use App\Models\Proposta;
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

                        // ALTERAÇÃO 1: Campo Sequencial agora é apenas informativo
                        Placeholder::make('sequencial_display')
                            ->label('Nº Sequencial')
                            ->content('Gerado automaticamente ao salvar'),
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
                            ->afterStateUpdated(fn(Set $set) => $set('embarcacao_id', null))
                            ->required(),

                        Select::make('embarcacao_id')
                            ->label('Embarcação (Opcional)')
                            ->options(function (Get $get) {
                                $clienteId = $get('cliente_id');
                                if (!$clienteId)
                                    return [];
                                return \App\Models\Embarcacao::where('cliente_id', $clienteId)
                                    ->pluck('nome_embarcacao', 'id');
                            })
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Serviços e Valores')
                    ->schema([
                        Repeater::make('itens_servico')
                            ->label('Lista de Serviços')
                            ->schema([
                                TextInput::make('descricao')->label('Descrição')->required()->columnSpan(2),
                                TextInput::make('valor')->label('Valor')->numeric()->prefix('R$')->live(onBlur: true)->required(),
                            ])
                            ->maxItems(11)
                            ->columns(3)
                            ->live(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('valor_desconto')->label('Desconto')->numeric()->prefix('R$')->default(0)->live(onBlur: true),
                                Placeholder::make('total_calculado')
                                    ->label('Total Líquido Estimado')
                                    ->content(fn(Get $get) => 'R$ ' . number_format(self::getTotalLiquido($get), 2, ',', '.'))
                                    ->extraAttributes(['class' => 'text-xl font-bold text-success-600']),
                            ]),
                    ]),

                Section::make('Condições de Pagamento')
                    ->schema([
                        Hidden::make('parcelas_count')->default(0)->dehydrated(false),

                        // ALTERAÇÃO 3: Repeater opcional, sem required
                        Repeater::make('parcelas')
                            ->label('Parcelamento (Opcional)')
                            ->schema([
                                TextInput::make('descricao')
                                    ->label('Descrição')
                                    ->placeholder('Ex: Entrada / 30 dias'),
                                // sem required()

                                TextInput::make('valor')
                                    ->label('Valor (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Get $get, Set $set, $component, $state) => self::recalcularCascata($get, $set, $component, $state)),
                                // sem required()
                            ])
                            ->maxItems(6)
                            ->defaultItems(0) // Começa vazio
                            ->columns(2)
                            ->addActionLabel('Adicionar Parcela')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $novaQtd = count($state);
                                if ($novaQtd !== (int) $get('parcelas_count')) {
                                    self::recalcularDivisaoIgual($get, $set);
                                    $set('parcelas_count', $novaQtd);
                                }
                            })
                            ->rules([
                                fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    // Se estiver vazio, pula a validação de soma
                                    if (empty($value) || count($value) === 0)
                                        return;

                                    $totalLiq = self::getTotalLiquido($get);
                                    $totalParc = round(collect($value)->sum(fn($p) => (float) ($p['valor'] ?? 0)), 2);

                                    if (abs($totalLiq - $totalParc) > 0.05) {
                                        $fail("A soma das parcelas difere do total.");
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
                // ALTERAÇÃO 2: Coluna Status removida
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('imprimir_contrato')->label('Contrato')->icon('heroicon-o-document-text')->color('primary')
                    ->url(fn(Proposta $record) => route('propostas.imprimir', $record->id))->openUrlInNewTab(),
                Tables\Actions\Action::make('imprimir_recibo')->label('Recibo')->icon('heroicon-o-receipt-percent')->color('success')
                    ->url(fn(Proposta $record) => route('propostas.imprimir_recibo', $record->id))->openUrlInNewTab(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPropostas::route('/'),
            'create' => Pages\CreateProposta::route('/create'),
            'edit' => Pages\EditProposta::route('/{record}/edit'),
        ];
    }

    protected static function getTotalLiquido(Get $get): float
    {
        $itens = $get('itens_servico') ?? [];
        $totalServico = collect($itens)->sum(fn($item) => (float) ($item['valor'] ?? 0));
        return round($totalServico - (float) ($get('valor_desconto') ?? 0), 2);
    }

    public static function recalcularCascata(Get $get, Set $set, $component, $novoValorState)
    {
        $totalLiquido = self::getTotalLiquido($get);
        $parcelas = $get('parcelas') ?? [];
        $uuidEditado = Str::afterLast(Str::beforeLast($component->getStatePath(), '.'), '.');
        $somaAnterior = 0;
        $encontrouAtual = false;
        $chavesFuturas = [];

        foreach ($parcelas as $uuid => $dados) {
            if ($encontrouAtual) {
                $chavesFuturas[] = $uuid;
                continue;
            }
            if ((string) $uuid === (string) $uuidEditado) {
                $somaAnterior += (float) $novoValorState;
                $encontrouAtual = true;
            } else {
                $somaAnterior += (float) ($dados['valor'] ?? 0);
            }
        }

        if (empty($chavesFuturas))
            return;
        $restante = round($totalLiquido - $somaAnterior, 2);
        $valorBase = floor(($restante / count($chavesFuturas)) * 100) / 100;
        $centavosSobra = round($restante - ($valorBase * count($chavesFuturas)), 2);

        foreach ($chavesFuturas as $index => $uuid) {
            $novo = $valorBase + ($index === count($chavesFuturas) - 1 ? $centavosSobra : 0);
            $parcelas[$uuid]['valor'] = number_format($novo, 2, '.', '');
        }
        $set('parcelas', $parcelas);
    }

    public static function recalcularDivisaoIgual(Get $get, Set $set)
    {
        $totalLiquido = self::getTotalLiquido($get);
        $parcelas = $get('parcelas');
        if (!$parcelas || count($parcelas) == 0)
            return;
        $qtd = count($parcelas);
        $valorBase = floor(($totalLiquido / $qtd) * 100) / 100;
        $diferenca = round($totalLiquido - ($valorBase * $qtd), 2);
        $i = 0;
        foreach ($parcelas as $uuid => $dados) {
            $parcelas[$uuid]['valor'] = number_format($valorBase + ($i === $qtd - 1 ? $diferenca : 0), 2, '.', '');
            $i++;
        }
        $set('parcelas', $parcelas);
    }
}