<?php

namespace App\Filament\Resources\EmbarcacaoResource\Pages;

use App\Filament\Resources\EmbarcacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;


class CreateEmbarcacao extends CreateRecord
{
    protected static string $resource = EmbarcacaoResource::class;

    // Altera o Título grande da página
    public function getTitle(): string 
    {
        return 'Cadastrar Embarcação';
    }

    // (Opcional) Altera o texto do caminho de navegação (Breadcrumb) no topo
    public function getBreadcrumb(): string 
    {
        return 'Cadastrar';
    }
}