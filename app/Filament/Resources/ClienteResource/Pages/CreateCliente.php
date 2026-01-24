<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;


class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    // Altera o Título grande da página
    public function getTitle(): string
    {
        return 'Cadastrar Cliente';
    }

    // (Opcional) Altera o texto do caminho de navegação (Breadcrumb) no topo
    public function getBreadcrumb(): string
    {
        return 'Cadastrar';
    }
}