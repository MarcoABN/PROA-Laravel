<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Models\SolicitacaoAgendamento;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Só a lista de clientes de um mês, para consulta e operação (copiar CPF/GRU, registrar, excluir).
 * Herda do mês o filtro pela competência e a ação de excluir o agendamento inteiro.
 *
 * Visual no estilo da grade do KellHub: linha de resumo, abas por capitania, colunas agrupadas por cor.
 */
class ClientesDoMesAgendamento extends VerMesAgendamento
{
    protected static string $view = 'filament.resources.agendamento-marinha.clientes';

    /** Grupo de cor de cada coluna (classes em clientes.blade.php). */
    private const GRUPOS_DE_COLUNA = [
        'agendamento.ordem' => 'proa-g-cliente',
        'cliente_nome' => 'proa-g-cliente',
        'cliente_documento' => 'proa-g-doc',
        'gru' => 'proa-g-doc',
        'servico.sigla' => 'proa-g-servico',
        'agendamento.status' => 'proa-g-status',
    ];

    public function getTitle(): string
    {
        return 'Clientes de ' . $this->dataCompetencia()->locale('pt_BR')->translatedFormat('F \d\e Y');
    }

    public function getBreadcrumbs(): array
    {
        return [
            AgendamentoMarinhaResource::getUrl() . '?ano=' . $this->dataCompetencia()->year => 'Agendamentos Marinha',
            AgendamentoMarinhaResource::getUrl('mes', ['competencia' => $this->competencia]) => $this->dataCompetencia()->format('m/Y'),
            'Clientes',
        ];
    }

    /** Filtros à vista: o principal aqui é "Só os não agendados". */
    public function table(Table $table): Table
    {
        $table = parent::table($table)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4);

        foreach ($table->getColumns() as $nome => $coluna) {
            if ($grupo = self::GRUPOS_DE_COLUNA[$nome] ?? null) {
                $coluna->extraHeaderAttributes(['class' => $grupo])->extraCellAttributes(['class' => $grupo]);
            }
        }

        return $table;
    }

    /** Uma aba por capitania com clientes no mês, como as abas de tipo do KellHub. */
    public function getTabs(): array
    {
        $porCapitania = $this->clientesDoMes()
            ->selectRaw('capitania_id, count(*) as total')
            ->groupBy('capitania_id')
            ->pluck('total', 'capitania_id');

        $tabs = ['todas' => Tab::make('Todas')->badge($porCapitania->sum())];

        Capitania::whereKey($porCapitania->keys())->orderByDesc('padrao')->orderBy('nome')->get()
            ->each(function (Capitania $capitania) use (&$tabs, $porCapitania) {
                $tabs["cap-{$capitania->id}"] = Tab::make($capitania->sigla ? "{$capitania->sigla} - {$capitania->nome}" : $capitania->nome)
                    ->badge($porCapitania[$capitania->id])
                    ->modifyQueryUsing(fn(Builder $query) => $query->where('capitania_id', $capitania->id));
            });

        // Com uma capitania só, as abas não ajudam em nada.
        return count($tabs) > 2 ? $tabs : [];
    }

    /**
     * Números do mês para a linha de resumo acima da grade.
     *
     * @return array{clientes: int, procuradores: int, agendados: int, aguardando: int, falharam: int, descartados: int}
     */
    public function resumo(): array
    {
        $clientes = $this->clientesDoMes()->with('agendamento:id,status')->get(['id', 'prestador_id', 'agendamento_marinha_id', 'descartada_em']);
        $ativos = $clientes->reject(fn(SolicitacaoAgendamento $s) => $s->descartada());
        $comStatus = fn(string $status) => $ativos->filter(fn(SolicitacaoAgendamento $s) => $s->agendamento?->status === $status)->count();

        return [
            'clientes' => $clientes->count(),
            'procuradores' => $clientes->pluck('prestador_id')->unique()->count(),
            'agendados' => $comStatus(AgendamentoMarinha::STATUS_AGENDADO),
            'aguardando' => $comStatus(AgendamentoMarinha::STATUS_PENDENTE),
            'falharam' => $comStatus(AgendamentoMarinha::STATUS_FALHOU),
            'descartados' => $clientes->count() - $ativos->count(),
        ];
    }

    private function clientesDoMes(): Builder
    {
        return SolicitacaoAgendamento::query()->whereDate('competencia', $this->dataCompetencia()->toDateString());
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('voltar')
                ->label('Agendamentos do mês')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AgendamentoMarinhaResource::getUrl('mes', ['competencia' => $this->competencia])),
        ];
    }
}
