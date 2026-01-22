<?php

namespace App\Filament\Resources\CapitaniaResource\Pages;

use App\Filament\Resources\CapitaniaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCapitania extends EditRecord
{
    protected static string $resource = CapitaniaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
