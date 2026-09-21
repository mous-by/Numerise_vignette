<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Canal par rôle (config/channels.php) : `channel:web` ou `channel:api`. Les deux portes ne se mélangent jamais.
 */
class EnsureChannelAllowed
{
    public function handle(Request $request, Closure $next, string $channel): Response
    {
        $user = $request->user();

        if ($user === null || $user->roleName()?->channel() === $channel) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Ce compte n\'est pas autorisé sur ce canal.', 'code' => 'channel_forbidden'], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['phone' => 'Ce compte n\'est pas autorisé sur la plateforme Web.']);
    }
}
