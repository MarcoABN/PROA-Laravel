<?php

namespace App\Filament\Resources\FinanceiroLogResource\Pages;

use App\Filament\Resources\FinanceiroLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFinanceiroLog extends ViewRecord
{
    protected static string $resource = FinanceiroLogResource::class;

    protected static ?string $title = 'Detalhes da Movimentação';
}
