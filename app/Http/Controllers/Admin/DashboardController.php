<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Panel del super administrador: métricas globales de la plataforma.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $totalTenants = Empresa::count();
        $activos = Empresa::where('estado_suscripcion', 'activa')->count();
        $enTrial = Empresa::where('estado_suscripcion', 'trial')->count();
        $suspendidos = Empresa::where('estado_suscripcion', 'suspendida')->count();

        // Usuarios de negocio (excluye super admins).
        $totalUsuarios = User::where('is_super', false)->count();

        // MRR: suma del precio del plan de los tenants con suscripción activa.
        $mrr = Empresa::where('empresas.estado_suscripcion', 'activa')
            ->join('planes', 'planes.id', '=', 'empresas.plan_id')
            ->sum('planes.precio');

        // Trials que vencen en los próximos 7 días.
        $trialsPorVencer = Empresa::with('plan')
            ->where('estado_suscripcion', 'trial')
            ->whereNotNull('trial_termina_en')
            ->whereBetween('trial_termina_en', [Carbon::today(), Carbon::today()->addDays(7)])
            ->orderBy('trial_termina_en')
            ->get();

        // Nuevos tenants por mes (últimos 6 meses).
        $altas = collect(range(5, 0))->map(function ($i) {
            $mes = Carbon::now()->subMonths($i);
            return [
                'label' => $mes->locale('es')->isoFormat('MMM YY'),
                'total' => Empresa::whereYear('created_at', $mes->year)
                    ->whereMonth('created_at', $mes->month)->count(),
            ];
        });

        $ultimosTenants = Empresa::with('plan')->latest()->limit(8)->get();
        $ultimasSuscripciones = Suscripcion::with(['empresa', 'plan'])->latest()->limit(8)->get();

        return view('admin.dashboard', compact(
            'totalTenants', 'activos', 'enTrial', 'suspendidos', 'totalUsuarios',
            'mrr', 'trialsPorVencer', 'altas', 'ultimosTenants', 'ultimasSuscripciones'
        ));
    }
}
