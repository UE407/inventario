<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso a la aplicación cuando la suscripción del tenant no está
 * vigente (trial vencido o suscripción caducada), redirigiendo a la página de
 * suscripción. Deja pasar siempre las rutas de suscripción y el logout.
 */
class EnsureSuscripcionActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        // Estas rutas deben ser accesibles aunque la suscripción no esté vigente.
        if ($request->routeIs('suscripcion.*', 'logout', 'impersonar.salir')) {
            return $next($request);
        }

        $empresa = Empresa::actual();

        if ($empresa && ! $empresa->suscripcionVigente()) {
            return redirect()->route('suscripcion.index')
                ->with('warning', 'Tu periodo de prueba o suscripción ha finalizado. Elige un plan para seguir usando el sistema.');
        }

        return $next($request);
    }
}
