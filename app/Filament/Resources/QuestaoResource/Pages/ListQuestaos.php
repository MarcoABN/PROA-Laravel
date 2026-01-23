<?php

namespace App\Filament\Resources\QuestaoResource\Pages;

use App\Filament\Resources\QuestaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuestaos extends ListRecords
{
    protected static string $resource = QuestaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
