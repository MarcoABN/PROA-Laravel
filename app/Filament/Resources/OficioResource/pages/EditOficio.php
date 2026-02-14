<?php

namespace App\Filament\Resources\OficioResource\Pages;

use App\Filament\Resources\OficioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOficio extends EditRecord
{
    protected static string $resource = OficioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
