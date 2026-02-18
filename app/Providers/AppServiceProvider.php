<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;      // <--- MANTENHA ISSO
use Illuminate\Auth\Events\Login;          // <--- MANTENHA ISSO
use App\Models\User;                       // <--- MANTENHA ISSO
use Illuminate\Support\Facades\URL;        // <--- MANTENHA ISSO

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Força HTTPS em produção (Necessário para o Docker)
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // 2. Registra o último login (Recurso que já existia)
        Event::listen(Login::class, function ($event) {
            if ($event->user instanceof User) {
                $event->user->update([
                    'last_login_at' => now(),
                ]);
            }
        });
    }
}
