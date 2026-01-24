<?php

namespace App\Filament\Resources\ProcessoResource\Pages;

use App\Filament\Resources\ProcessoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcessos extends ListRecords
{
    protected static string $resource = ProcessoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
