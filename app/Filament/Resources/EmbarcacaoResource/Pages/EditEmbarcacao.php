<?php

namespace App\Filament\Resources\EmbarcacaoResource\Pages;

use App\Filament\Resources\EmbarcacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmbarcacao extends EditRecord
{
    protected static string $resource = EmbarcacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
