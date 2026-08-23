<?php

namespace App\Filament\Resources\CategoriaFinanceiraResource\Pages;

use App\Filament\Resources\CategoriaFinanceiraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoriaFinanceiras extends ListRecords
{
    protected static string $resource = CategoriaFinanceiraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Novo Tipo'),
        ];
    }
}
