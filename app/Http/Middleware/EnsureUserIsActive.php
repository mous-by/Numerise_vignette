<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compte ET institution actifs (ARCHITECTURE §11). Vérifié à chaque requête : désactiver un utilisateur ou une
 * institution coupe l'accès immédiatement, sans attendre l'expiration de la session ou du jeton.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->canAuthenticate()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            $user->currentAccessToken()?->delete();

            return response()->json(['message' => 'Compte ou institution désactivé.', 'code' => 'account_disabled'], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['phone' => 'Ce compte est désactivé ou son institution est désactivée.']);
    }
}
