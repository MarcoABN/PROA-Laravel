<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Livewire\PerformanceStats; 
use App\Livewire\PerformanceTable;

class GestaoPerformance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Gestão de Performance';
    protected static ?string $title = 'Performance da Equipe';
    protected static ?string $navigationGroup = 'Painel de Controle';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.gestao-performance';

    // Permitir adicionar widgets no cabeçalho ou rodapé da página
    protected function getHeaderWidgets(): array
    {
        return [
            PerformanceStats::class,
            PerformanceTable::class, 
        ];
    }
}