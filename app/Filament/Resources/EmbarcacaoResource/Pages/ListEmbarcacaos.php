<?php

namespace App\Filament\Resources\EmbarcacaoResource\Pages;

use App\Filament\Resources\EmbarcacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmbarcacaos extends ListRecords
{
    protected static string $resource = EmbarcacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Cadastrar Embarcação'),
        ];
    }
}
