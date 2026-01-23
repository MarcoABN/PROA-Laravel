<?php

namespace App\Filament\Resources\QuestaoResource\Pages;

use App\Filament\Resources\QuestaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuestao extends EditRecord
{
    protected static string $resource = QuestaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
