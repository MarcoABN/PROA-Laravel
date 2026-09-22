<?php

namespace App\Filament\Resources\ArtigoResource\Pages;

use App\Filament\Resources\ArtigoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArtigo extends EditRecord
{
    protected static string $resource = ArtigoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
