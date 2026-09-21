<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tant que le mot de passe est temporaire (D14), seule la page de changement de mot de passe est accessible.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Vous devez changer votre mot de passe.', 'code' => 'password_change_required'], 403);
        }

        return redirect()->route('password.change');
    }
}
