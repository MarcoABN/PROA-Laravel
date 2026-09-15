<?php

namespace App\Filament\Resources\SisapServicoResource\Pages;

use App\Filament\Resources\SisapServicoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSisapServicos extends ListRecords
{
    protected static string $resource = SisapServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Novo Serviço'),
        ];
    }
}
