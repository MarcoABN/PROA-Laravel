<?php

namespace App\Filament\Pages;

use App\Models\LancamentoFinanceiro;
use App\Support\AcessoFinanceiro;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * Visão consolidada: totais do período, fluxo de cada pessoa (mais a Empresa)
 * e ranking dos tipos que mais consomem dinheiro.
 */
class ConsolidadoFinanceiro extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationLabel = 'Consolidado';
    protected static ?string $navigationGroup = 'Gestão Financeira';
    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Consolidado Financeiro';
    protected static ?string $slug = 'consolidado-financeiro';

    protected static string $view = 'filament.pages.consolidado-financeiro';

    /** @var array<string, mixed> */
    public ?array $filtros = [];

    public static function canAccess(): bool
    {
        return AcessoFinanceiro::permitido();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return AcessoFinanceiro::permitido();
    }

    public function mount(): void
    {
        $this->form->fill([
            'periodo' => 'mes_atual',
            'de'  => now()->startOfMonth()->toDateString(),
            'ate' => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('filtros')
            ->schema([
                Grid::make(3)->schema([
                    Select::make('periodo')
                        ->label('Período')
                        ->options([
                            'mes_atual'     => 'Mês atual',
                            'mes_anterior'  => 'Mês anterior',
                            'ano_atual'     => 'Ano atual',
                            'tudo'          => 'Todo o histórico',
                            'personalizado' => 'Personalizado',
                        ])
                        ->default('mes_atual')
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            [$de, $ate] = match ($state) {
                                'mes_atual'    => [now()->startOfMonth(), now()->endOfMonth()],
                                'mes_anterior' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
                                'ano_atual'    => [now()->startOfYear(), now()->endOfYear()],
                                'tudo'         => [null, null],
                                default        => [null, null],
                            };

                            // No modo personalizado as datas ficam como estão,
                            // para o usuário digitar sem serem sobrescritas.
                            if ($state === 'personalizado') {
                                return;
                            }

                            $set('de', $de?->toDateString());
                            $set('ate', $ate?->toDateString());
                        }),

                    DatePicker::make('de')
                        ->label('De')
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->live(),

                    DatePicker::make('ate')
                        ->label('Até')
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->live(),
                ]),
            ]);
    }

    protected function intervalo(): array
    {
        return [
            $this->filtros['de'] ?? null,
            $this->filtros['ate'] ?? null,
        ];
    }

    /**
     * Entradas, saídas e saldo do período inteiro.
     */
    public function getTotaisProperty(): array
    {
        [$de, $ate] = $this->intervalo();

        $porTipo = LancamentoFinanceiro::query()
            ->noPeriodo($de, $ate)
            ->selectRaw('tipo, SUM(valor) as total, COUNT(*) as quantidade')
            ->groupBy('tipo')
            ->get()
            ->keyBy('tipo');

        $entradas = (float) ($porTipo[LancamentoFinanceiro::TIPO_ENTRADA]->total ?? 0);
        $saidas   = (float) ($porTipo[LancamentoFinanceiro::TIPO_SAIDA]->total ?? 0);

        return [
            'entradas'   => $entradas,
            'saidas'     => $saidas,
            'saldo'      => $entradas - $saidas,
            'quantidade' => (int) ($porTipo[LancamentoFinanceiro::TIPO_ENTRADA]->quantidade ?? 0)
                + (int) ($porTipo[LancamentoFinanceiro::TIPO_SAIDA]->quantidade ?? 0),
        ];
    }

    /**
     * Uma linha por pessoa + uma linha "Empresa", cada uma com seu fluxo.
     */
    public function getPorResponsavelProperty(): array
    {
        [$de, $ate] = $this->intervalo();

        $linhas = LancamentoFinanceiro::query()
            ->noPeriodo($de, $ate)
            ->leftJoin('users', 'users.id', '=', 'lancamentos_financeiros.user_id')
            ->selectRaw('lancamentos_financeiros.escopo, lancamentos_financeiros.user_id, users.name as usuario, lancamentos_financeiros.tipo, SUM(lancamentos_financeiros.valor) as total, COUNT(*) as quantidade')
            ->groupBy('lancamentos_financeiros.escopo', 'lancamentos_financeiros.user_id', 'users.name', 'lancamentos_financeiros.tipo')
            ->get();

        $agrupado = [];

        foreach ($linhas as $linha) {
            $chave = $linha->escopo === LancamentoFinanceiro::ESCOPO_EMPRESA
                ? 'empresa'
                : 'user:' . ($linha->user_id ?? 'sem');

            $agrupado[$chave] ??= [
                'nome' => $linha->escopo === LancamentoFinanceiro::ESCOPO_EMPRESA
                    ? 'Empresa'
                    : ($linha->usuario ?? 'Sem usuário'),
                'empresa'    => $linha->escopo === LancamentoFinanceiro::ESCOPO_EMPRESA,
                'entradas'   => 0.0,
                'saidas'     => 0.0,
                'quantidade' => 0,
            ];

            $campo = $linha->tipo === LancamentoFinanceiro::TIPO_ENTRADA ? 'entradas' : 'saidas';

            $agrupado[$chave][$campo] += (float) $linha->total;
            $agrupado[$chave]['quantidade'] += (int) $linha->quantidade;
        }

        foreach ($agrupado as &$item) {
            $item['saldo'] = $item['entradas'] - $item['saidas'];
        }
        unset($item);

        // Empresa primeiro, depois quem mais gastou.
        uasort($agrupado, function (array $a, array $b) {
            if ($a['empresa'] !== $b['empresa']) {
                return $a['empresa'] ? -1 : 1;
            }

            return $b['saidas'] <=> $a['saidas'];
        });

        return array_values($agrupado);
    }

    /**
     * Ranking de categorias. $tipo = entrada | saida.
     */
    protected function ranking(string $tipo): array
    {
        [$de, $ate] = $this->intervalo();

        $linhas = LancamentoFinanceiro::query()
            ->where('lancamentos_financeiros.tipo', $tipo)
            ->noPeriodo($de, $ate)
            ->join('categorias_financeiras', 'categorias_financeiras.id', '=', 'lancamentos_financeiros.categoria_financeira_id')
            ->selectRaw('categorias_financeiras.nome as categoria, SUM(lancamentos_financeiros.valor) as total, COUNT(*) as quantidade')
            ->groupBy('categorias_financeiras.nome')
            ->orderByRaw('SUM(lancamentos_financeiros.valor) DESC')
            ->limit(10)
            ->get();

        $soma = (float) $linhas->sum('total');

        return $linhas->map(fn($linha) => [
            'categoria'   => $linha->categoria,
            'total'       => (float) $linha->total,
            'quantidade'  => (int) $linha->quantidade,
            'percentual'  => $soma > 0 ? ((float) $linha->total / $soma) * 100 : 0,
        ])->all();
    }

    public function getMaioresGastosProperty(): array
    {
        return $this->ranking(LancamentoFinanceiro::TIPO_SAIDA);
    }

    public function getMaioresEntradasProperty(): array
    {
        return $this->ranking(LancamentoFinanceiro::TIPO_ENTRADA);
    }

    public function getRotuloPeriodoProperty(): string
    {
        [$de, $ate] = $this->intervalo();

        if (! $de && ! $ate) {
            return 'Todo o histórico';
        }

        $inicio = $de ? Carbon::parse($de)->format('d/m/Y') : 'início';
        $fim    = $ate ? Carbon::parse($ate)->format('d/m/Y') : 'hoje';

        return "{$inicio} a {$fim}";
    }
}
