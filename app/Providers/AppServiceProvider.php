<?php

namespace App\Providers;

use App\Services\CultivoService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Inyecta las alertas (cultivos que requieren atención) en el layout
        // principal para que la campana de notificaciones funcione en
        // cualquier página, no solo en el Dashboard.
        View::composer('layouts.app', function ($view) {
            if (!session('authenticated')) {
                $view->with('navAlertas', collect());
                return;
            }

            try {
                $cultivos = app(CultivoService::class)->all();
                $alertas = collect($cultivos)->filter(fn ($c) => $c['requiere_atencion']);
            } catch (\Throwable $e) {
                $alertas = collect();
            }

            $view->with('navAlertas', $alertas);
        });
    }
}
