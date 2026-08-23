<?php

namespace App\Filament\Resources\LancamentoFinanceiroResource\Pages;

use App\Filament\Resources\LancamentoFinanceiroResource;
use App\Models\LancamentoFinanceiro;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLancamentoFinanceiros extends ListRecords
{
    protected static string $resource = LancamentoFinanceiroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Novo Lançamento'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),

            'entradas' => Tab::make('Entradas')
                ->modifyQueryUsing(fn(Builder $query) => $query->entradas()),

            'saidas' => Tab::make('Saídas')
                ->modifyQueryUsing(fn(Builder $query) => $query->saidas()),

            'empresa' => Tab::make('Empresa')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('escopo', LancamentoFinanceiro::ESCOPO_EMPRESA)),

            'meus' => Tab::make('Meus lançamentos')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('user_id', auth()->id())),
        ];
    }
}
