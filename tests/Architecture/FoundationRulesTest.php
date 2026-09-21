<?php

namespace Tests\Architecture;

use App\Models\ActivityLog;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Garde-fous du socle (ARCHITECTURE §8, §15) : ils échouent si une règle est enfreinte.
 */
class FoundationRulesTest extends TestCase
{
    public function test_every_business_model_uses_logs_activity(): void
    {
        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            if (! is_subclass_of($class, Model::class) || $class === ActivityLog::class) {
                continue;
            }

            $this->assertContains(LogsActivity::class, class_uses_recursive($class), "$class doit utiliser LogsActivity (audit).");
        }
    }

    public function test_no_bulk_update_or_delete_on_the_audit_log(): void
    {
        foreach ($this->phpFiles(app_path()) as $file) {
            $code = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/ActivityLog::(query\(\)->)?(where|whereIn|whereKey)[^;]*->(update|delete|forceDelete|truncate)\(/s', $code, "$file : mise à jour ou suppression de masse de l'audit interdite.");
            $this->assertDoesNotMatchRegularExpression('/ActivityLog::(truncate|destroy)\(/', $code, "$file : suppression de l'audit interdite.");
        }
    }

    public function test_no_weak_password_rule_anywhere(): void
    {
        foreach ($this->phpFiles(app_path()) as $file) {
            if (preg_match_all('/Password::min\((\d+)\)/', file_get_contents($file), $m)) {
                foreach ($m[1] as $length) {
                    $this->assertGreaterThanOrEqual(8, (int) $length, "$file : Password::min($length) < 8 (D14).");
                }
            }
        }
    }

    public function test_views_and_local_assets_load_nothing_from_the_internet(): void
    {
        $offenders = [];

        foreach ($this->filesIn(resource_path('views'), ['php']) as $file) {
            // xmlns SVG mis à part, aucune URL absolue dans les vues.
            $code = preg_replace('#https?://www\.w3\.org/[^\s"\']*#', '', file_get_contents($file));
            if (preg_match('#https?://#', $code)) {
                $offenders[] = $file;
            }
        }

        foreach ([public_path('assets/css'), public_path('assets/plugins')] as $dir) {
            foreach ($this->filesIn($dir, ['css']) as $file) {
                if (preg_match('#(url\(\s*[\'"]?https?:|@import[^;]*https?:)#i', file_get_contents($file))) {
                    $offenders[] = $file;
                }
            }
        }

        $this->assertSame([], $offenders, 'Ressources externes interdites (D15) : '.implode(', ', $offenders));
    }

    public function test_every_route_is_authenticated_and_permission_protected_or_explicitly_allowed(): void
    {
        // Routes accessibles à tout utilisateur connecté, sans permission métier (ARCHITECTURE §12).
        $authenticatedOnly = ['home', 'logout', 'password.change', 'password.change.update', 'profile.show', 'profile.update', 'profile.password', 'modules.show', 'api.logout', 'api.password', 'api.me'];
        $public = ['login', 'login.store'];
        $publicApi = ['api.health', 'api.login'];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($route->uri() === 'up' || str_starts_with((string) $name, 'storage.')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            if (str_starts_with($route->uri(), 'api/')) {
                // API : jetons Bearer, sans session (pas de groupe `web`), toujours en JSON.
                $this->assertNotContains('web', $middleware, "{$route->uri()} ne doit pas passer par le groupe web (session, CSRF).");
                if (in_array($name, $publicApi, true)) {
                    continue;
                }
                foreach (['auth:sanctum', 'active', 'channel:api'] as $required) {
                    $this->assertContains($required, $middleware, "{$route->uri()} doit porter {$required}.");
                }
                $hasPermission = collect($middleware)->contains(fn ($m) => is_string($m) && str_starts_with($m, 'permission:'));
                $this->assertTrue($hasPermission || in_array($name, $authenticatedOnly, true), "{$route->uri()} ({$name}) doit porter permission:module.action ou figurer dans la liste blanche.");

                continue;
            }

            if (in_array($name, $public, true)) {
                $this->assertContains('guest', $middleware, "{$route->uri()} doit être réservée aux invités.");

                continue;
            }

            $this->assertContains('auth', $middleware, "{$route->uri()} doit exiger l'authentification.");
            $this->assertContains('active', $middleware, "{$route->uri()} doit vérifier compte et institution actifs.");
            $this->assertContains('channel:web', $middleware, "{$route->uri()} doit être limitée au canal Web.");

            $hasPermission = collect($middleware)->contains(fn ($m) => is_string($m) && str_starts_with($m, 'permission:'));
            $this->assertTrue($hasPermission || in_array($name, $authenticatedOnly, true), "{$route->uri()} ({$name}) doit porter permission:module.action ou figurer dans la liste blanche.");
        }
    }

    public function test_no_role_middleware_is_used_in_routes(): void
    {
        foreach (glob(base_path('routes/web/*.php')) as $file) {
            $this->assertStringNotContainsString("'role:", file_get_contents($file), "$file : utiliser permission:module.action, pas role: (ARCHITECTURE §7).");
        }
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $dir): array
    {
        return $this->filesIn($dir, ['php']);
    }

    /**
     * @param  list<string>  $extensions
     * @return list<string>
     */
    private function filesIn(string $dir, array $extensions): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (in_array($file->getExtension(), $extensions, true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
