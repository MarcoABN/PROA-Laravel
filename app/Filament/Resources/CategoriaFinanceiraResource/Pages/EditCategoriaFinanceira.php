<?php

namespace App\Filament\Resources\CategoriaFinanceiraResource\Pages;

use App\Filament\Resources\CategoriaFinanceiraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoriaFinanceira extends EditRecord
{
    protected static string $resource = CategoriaFinanceiraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
