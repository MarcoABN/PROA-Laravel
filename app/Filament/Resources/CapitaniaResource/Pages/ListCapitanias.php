<?php

namespace App\Filament\Resources\CapitaniaResource\Pages;

use App\Filament\Resources\CapitaniaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCapitanias extends ListRecords
{
    protected static string $resource = CapitaniaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
