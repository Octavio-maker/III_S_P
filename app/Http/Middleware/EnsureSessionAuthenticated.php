<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('authenticated')) {
            return redirect()->route('login')->with('error', 'Tu sesión expiró. Inicia sesión de nuevo.');
        }

        return $next($request);
    }
}
