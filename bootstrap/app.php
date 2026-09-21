<?php

use App\Http\Middleware\EnsureChannelAllowed;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureSuperadminsExist;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJson;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'channel' => EnsureChannelAllowed::class,
            'password.changed' => EnsurePasswordChanged::class,
            'superadmins.ensure' => EnsureSuperadminsExist::class,
            // Toutes les routes sont protégées par `permission:module.action` (pas de `role:` dans les routes, ARCHITECTURE §7).
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // L'API répond toujours en JSON, sans session ni CSRF (D12).
        $middleware->api(prepend: [ForceJson::class]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Messages JSON en français pour l'application mobile (le Web garde ses redirections et ses pages d'erreur).
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }
        });
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Vous n\'avez pas les permissions nécessaires.', 'code' => 'forbidden'], 403);
            }
        });
    })->create();
