<?php

namespace App\Filament\Resources\EscolaNauticaResource\Pages;

use App\Filament\Resources\EscolaNauticaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;


class CreateEscolaNautica extends CreateRecord
{
    protected static string $resource = EscolaNauticaResource::class;

    // Altera o Título grande da página
    public function getTitle(): string 
    {
        return 'Cadastrar Escola Nautica';
    }

    // (Opcional) Altera o texto do caminho de navegação (Breadcrumb) no topo
    public function getBreadcrumb(): string 
    {
        return 'Cadastrar';
    }
}