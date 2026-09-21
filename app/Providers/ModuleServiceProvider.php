<?php

namespace App\Providers;

use App\Support\ModuleRegistry;
use App\Support\PlannedModules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Modules par manifeste (D7) : les manifestes config/modules/*.php sont chargés par Laravel (répertoire de
 * configuration imbriqué) ; ce fournisseur expose le registre et alimente le menu de la sidebar.
 * Un module s'ajoute sans modifier ce fichier.
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
    }

    public function boot(): void
    {
        View::composer('partials.sidebar', function ($view) {
            $user = Auth::user();

            $view->with('navigation', $user ? app(ModuleRegistry::class)->navigation($user) : []);
            $view->with('plannedModules', $user ? app(PlannedModules::class)->visibleTo($user) : []);
        });
    }
}
