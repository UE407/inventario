<?php

namespace App\Http\Middleware;

use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el área /admin al super administrador de la plataforma.
 * Además limpia el tenant activo para que las consultas vean TODOS los
 * negocios (el panel opera por encima del aislamiento por empresa).
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_super) {
            abort(403, 'Acceso exclusivo del super administrador.');
        }

        Tenant::clear();

        return $next($request);
    }
}
