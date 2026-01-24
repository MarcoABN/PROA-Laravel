<?php

namespace App\Livewire;

use App\Models\Cliente;
use App\Models\Embarcacao; // Certifique-se de importar o Model
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class PerformanceStats extends BaseWidget
{
    protected function getStats(): array
    {
        $inicioMes = Carbon::now()->startOfMonth();

        return [
            Stat::make('Total de Clientes', Cliente::count())
                ->description('Cadastros totais no sistema')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Novos este Mês', Cliente::where('created_at', '>=', $inicioMes)->count())
                ->description('Clientes cadastrados este mês')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            // ALTERADO: Agora mostra contagem absoluta de barcos novos
            Stat::make('Novas Embarcações', Embarcacao::where('created_at', '>=', $inicioMes)->count())
                ->description('Cadastradas este mês')
                ->descriptionIcon('heroicon-m-lifebuoy') // Ícone de boia/barco
                ->color('warning'),
        ];
    }
}