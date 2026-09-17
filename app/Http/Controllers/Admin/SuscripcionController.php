<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

/**
 * Listado global de suscripciones (super admin).
 */
class SuscripcionController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $estado = $request->get('estado');
        $planId = $request->get('plan');
        $q = $request->get('q');

        $suscripciones = $this->filtrar($request)->paginate(25)->withQueryString();
        $planes = Plan::orderBy('orden')->get();

        return view('admin.suscripciones.index', compact('suscripciones', 'planes', 'estado', 'planId', 'q'));
    }

    public function export(Request $request)
    {
        $filas = $this->filtrar($request)->get()->map(fn ($s) => [
            $s->created_at->format('d/m/Y H:i'),
            $s->empresa->nombre ?? '—',
            $s->plan->nombre ?? '—',
            $s->estado,
            number_format($s->monto, 2),
            optional($s->inicia_en)->format('d/m/Y'),
            optional($s->termina_en)->format('d/m/Y'),
        ]);

        return $this->descargarCsv('suscripciones',
            ['Fecha', 'Negocio', 'Plan', 'Estado', 'Monto', 'Inicia', 'Termina'], $filas);
    }

    private function filtrar(Request $request)
    {
        return Suscripcion::with(['empresa', 'plan'])
            ->when($request->get('estado'), fn ($q, $v) => $q->where('estado', $v))
            ->when($request->get('plan'), fn ($q, $v) => $q->where('plan_id', $v))
            ->when($request->get('q'), fn ($q, $v) => $q->whereHas('empresa', fn ($e) => $e->where('nombre', 'like', "%{$v}%")))
            ->latest();
    }
}
