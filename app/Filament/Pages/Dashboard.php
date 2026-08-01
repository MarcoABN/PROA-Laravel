<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Página inicial do painel.
 *
 * Existe apenas para renomear a Dashboard padrão do Filament, que em pt-BR vinha
 * como "Painel de Controle" e se confundia com o grupo de menu de mesmo nome
 * (Questões do Simulado, Serviços do Site, Gestão de Performance e Usuários).
 *
 * Definir $title já resolve o rótulo do menu e o título da página: o
 * getNavigationLabel() do Filament usa $navigationLabel ?? $title ?? tradução.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Início';
}
