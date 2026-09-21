<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * L'API répond toujours en JSON, même si le client a oublié `Accept: application/json` : jamais de redirection ni de page HTML.
 */
class ForceJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
