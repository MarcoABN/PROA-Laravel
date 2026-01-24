<?php

namespace App\Filament\Resources\PropostaResource\Pages;

use App\Filament\Resources\PropostaResource;
use App\Models\Proposta;
use Filament\Resources\Pages\CreateRecord;

class CreateProposta extends CreateRecord
{
    protected static string $resource = PropostaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Lógica de Sequencial Automático Diário
        $date = $data['data_proposta'];

        // Busca o maior sequencial para esta data específica
        $ultimoSequencial = Proposta::whereDate('data_proposta', $date)
            ->max('sequencial_diario');

        // Se encontrou, soma 1. Se não, começa do 1.
        $data['sequencial_diario'] = $ultimoSequencial ? $ultimoSequencial + 1 : 1;

        return $data;
    }
}