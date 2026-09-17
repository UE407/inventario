<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Support\ExportsCsv;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reportes de negocio de la plataforma (super admin).
 */
class ReporteController extends Controller
{
    use ExportsCsv;

    public function index()
    {
        $porEstado = Empresa::select('estado_suscripcion', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_suscripcion')->pluck('total', 'estado_suscripcion');

        $mrr = Empresa::where('empresas.estado_suscripcion', 'activa')
            ->join('planes', 'planes.id', '=', 'empresas.plan_id')
            ->sum('planes.precio');

        // MRR por plan (tenants activos).
        $porPlan = Plan::orderBy('orden')->get()->map(function ($plan) {
            $activos = Empresa::where('plan_id', $plan->id)->where('estado_suscripcion', 'activa')->count();
            return [
                'plan' => $plan->nombre,
                'precio' => (float) $plan->precio,
                'activos' => $activos,
                'mrr' => $activos * (float) $plan->precio,
            ];
        });

        // Serie mensual (12 meses): altas de tenants e ingresos registrados.
        $serie = collect(range(11, 0))->map(function ($i) {
            $mes = Carbon::now()->subMonths($i);
            return [
                'label' => $mes->locale('es')->isoFormat('MMM YY'),
                'altas' => Empresa::whereYear('created_at', $mes->year)->whereMonth('created_at', $mes->month)->count(),
                'ingresos' => (float) Suscripcion::where('estado', 'activa')
                    ->whereYear('created_at', $mes->year)->whereMonth('created_at', $mes->month)->sum('monto'),
            ];
        });

        $totalTenants = Empresa::count();

        return view('admin.reportes.index', compact('porEstado', 'mrr', 'porPlan', 'serie', 'totalTenants'));
    }

    public function export()
    {
        $filas = collect(range(11, 0))->map(function ($i) {
            $mes = Carbon::now()->subMonths($i);
            return [
                $mes->locale('es')->isoFormat('MMMM YYYY'),
                Empresa::whereYear('created_at', $mes->year)->whereMonth('created_at', $mes->month)->count(),
                number_format((float) Suscripcion::where('estado', 'activa')
                    ->whereYear('created_at', $mes->year)->whereMonth('created_at', $mes->month)->sum('monto'), 2),
            ];
        });

        return $this->descargarCsv('reporte_plataforma',
            ['Mes', 'Nuevos tenants', 'Ingresos'], $filas);
    }
}
