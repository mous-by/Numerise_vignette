<?php

namespace App\Providers;

use App\Models\Commissariat;
use App\Models\Mairie;
use App\Models\Permission;
use App\Models\User;
use App\Services\Users\SuperadminBootstrapper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SuperadminBootstrapper::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Le superadmin contourne l'autorisation (Gate::before), pas la logique métier ni l'audit (ARCHITECTURE §1, D16).
        Gate::before(fn ($user, string $ability) => $user instanceof User && $user->isSuperadmin() ? true : null);

        // D14 : 8 caractères au moins, lettres et chiffres.
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Superadmins créés automatiquement s'ils n'existent pas (D27) : au lancement du serveur de développement et après
        // chaque migration (déploiement). La première visite de la page de connexion est le filet pour l'hébergement Web.
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if ($event->command === 'serve') {
                app(SuperadminBootstrapper::class)->ensure();
            }
        });
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if ($event->exitCode === 0 && in_array($event->command, ['migrate', 'migrate:fresh', 'migrate:refresh', 'permissions:sync'], true)) {
                app(SuperadminBootstrapper::class)->ensure();
            }
        });

        // Limitation de l'API : 120 requêtes par minute et par utilisateur (ou par IP pour les appels publics).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->getKey() ?: $request->ip()));

        // Routes publiques de la population (D32, §4.9) : sans jeton, donc limitées par IP seulement. Introduit avec
        // W6 (informations) ; réutilisé par les futures routes publiques (motos retrouvées, demande VGT).
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        // Écriture et suivi publics de la population (demande de VGT, M4) : 6 par minute et par IP, et 10 par heure
        // par matricule (contre le devinage du téléphone du propriétaire, qui sert d'identification, D32).
        RateLimiter::for('public-write', fn (Request $request) => [
            Limit::perMinute(6)->by('ip:'.$request->ip()),
            Limit::perHour(10)->by('plate:'.strtoupper(trim((string) $request->input('matricule')))),
        ]);

        // Alias morph stables : ils s'écrivent dans activity_logs, model_has_roles et personal_access_tokens.
        Relation::morphMap([
            'user' => User::class,
            'commissariat' => Commissariat::class,
            'mairie' => Mairie::class,
            'permission' => Permission::class,
            'role' => Role::class,
        ]);
    }
}
