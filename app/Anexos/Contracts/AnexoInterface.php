<?php

namespace App\Anexos\Contracts;

interface AnexoInterface
{
    public function getTitulo(): string;
    public function getFormSchema(): array;
    public function getTemplatePath(): string;
    // Alterado: $record pode ser Embarcacao ou Cliente
    public function getDados($record, array $input): array; 
}