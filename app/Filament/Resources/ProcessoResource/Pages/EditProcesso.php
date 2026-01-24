<?php

namespace App\Filament\Resources\ProcessoResource\Pages;

use App\Filament\Resources\ProcessoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcesso extends EditRecord
{
    protected static string $resource = ProcessoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
