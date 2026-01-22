<?php

namespace App\Filament\Resources\EscolaNauticaResource\Pages;

use App\Filament\Resources\EscolaNauticaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEscolaNautica extends EditRecord
{
    protected static string $resource = EscolaNauticaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
