<?php

namespace App\Filament\Resources\LancamentoFinanceiroResource\Pages;

use App\Filament\Resources\LancamentoFinanceiroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLancamentoFinanceiro extends EditRecord
{
    protected static string $resource = LancamentoFinanceiroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
