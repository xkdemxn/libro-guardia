<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers());

        // Cantidad de solicitudes de confirmación pendientes para el aviso de la barra superior.
        View::composer(['layouts.navigation', 'contactos.index'], function (\Illuminate\View\View $view): void {
            $view->with('solicitudesPendientes', auth()->user()?->solicitudesPendientes()->count() ?? 0);
        });
    }
}
