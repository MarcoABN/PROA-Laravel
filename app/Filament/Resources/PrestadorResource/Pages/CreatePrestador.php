<?php

namespace App\Filament\Resources\PrestadorResource\Pages;

use App\Filament\Resources\PrestadorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePrestador extends CreateRecord
{
    protected static string $resource = PrestadorResource::class;

    // Altera o Título grande da página
    public function getTitle(): string 
    {
        return 'Cadastrar Prestador';
    }

    // (Opcional) Altera o texto do caminho de navegação (Breadcrumb) no topo
    public function getBreadcrumb(): string 
    {
        return 'Cadastrar';
    }
}
