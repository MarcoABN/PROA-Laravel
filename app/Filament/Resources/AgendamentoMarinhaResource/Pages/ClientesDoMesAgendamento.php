<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use Filament\Actions;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

/**
 * Só a lista de clientes de um mês, para consulta e operação (copiar CPF/GRU, registrar, excluir).
 * Herda do mês o filtro pela competência e a ação de excluir o agendamento inteiro.
 */
class ClientesDoMesAgendamento extends VerMesAgendamento
{
    protected static string $view = 'filament.resources.agendamento-marinha.clientes';

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
        return parent::table($table)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4);
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
