<?php

namespace App\Filament\Resources\FinanceiroLogResource\Pages;

use App\Filament\Resources\FinanceiroLogResource;
use App\Models\FinanceiroLog;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFinanceiroLogs extends ListRecords
{
    protected static string $resource = FinanceiroLogResource::class;

    // Sem CreateAction: o histórico é escrito pelo sistema, não pela tela.
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Tudo'),

            'criados' => Tab::make('Criados')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('evento', FinanceiroLog::EVENTO_CRIADO)),

            'alterados' => Tab::make('Alterados')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('evento', FinanceiroLog::EVENTO_ATUALIZADO)),

            'excluidos' => Tab::make('Excluídos')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('evento', FinanceiroLog::EVENTO_EXCLUIDO)),

            'meus' => Tab::make('Minhas ações')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('user_id', auth()->id())),
        ];
    }
}
