<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('user_role') !== 'Administrador') {
            return redirect()->route('dashboard')
                ->with('error', 'Acceso denegado: se requieren permisos de Administrador.');
        }

        return $next($request);
    }
}