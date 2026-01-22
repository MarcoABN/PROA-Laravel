<?php

namespace App\Filament\Resources\EscolaNauticaResource\Pages;

use App\Filament\Resources\EscolaNauticaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEscolaNauticas extends ListRecords
{
    protected static string $resource = EscolaNauticaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Cadastrar Nova Escola'),
        ];
    }
}
