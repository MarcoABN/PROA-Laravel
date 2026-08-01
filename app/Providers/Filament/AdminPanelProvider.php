<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Support\PreferenciaNavegacao;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->favicon(asset('favicon.png'))
            ->brandLogo(asset('images/logo-proa.png'))
            ->brandLogoHeight('3rem')
            ->brandName('PROA')

            // Conteúdo ocupa a largura toda da tela. O padrão do Filament é 7xl (1280px),
            // o que deixava faixas vazias nas laterais em monitores largos e empurrava
            // as informações para baixo, exigindo scroll desnecessário.
            ->maxContentWidth(MaxWidth::Full)

            // Menu lateral recolhível também no desktop, não só em telas pequenas.
            ->sidebarCollapsibleOnDesktop()

            // Posição do menu escolhida pelo usuário (lateral ou superior).
            ->topNavigation(fn(): bool => PreferenciaNavegacao::ehSuperior())

            ->userMenuItems([
                MenuItem::make()
                    ->label(fn(): string => PreferenciaNavegacao::ehSuperior()
                        ? 'Usar menu lateral'
                        : 'Usar menu superior')
                    ->icon(fn(): string => PreferenciaNavegacao::ehSuperior()
                        ? 'heroicon-o-view-columns'
                        : 'heroicon-o-bars-3')
                    ->url(fn(): string => route('preferencias.menu', [
                        'posicao' => PreferenciaNavegacao::ehSuperior()
                            ? PreferenciaNavegacao::LATERAL
                            : PreferenciaNavegacao::SUPERIOR,
                    ])),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Dashboard própria (App\Filament\Pages\Dashboard), rotulada "Início"
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                //Widgets\FilamentInfoWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn(): View => view('filament.components.normam-modal'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
