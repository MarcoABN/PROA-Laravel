<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use App\Models\User;

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
        // Adicione este bloco:
        Event::listen(Login::class, function ($event) {
            if ($event->user instanceof User) {
                $event->user->update([
                    'last_login_at' => now(),
                ]);
            }
        });
    }
}
