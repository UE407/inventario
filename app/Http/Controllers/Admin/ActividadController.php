<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActividadLog;
use Illuminate\Http\Request;

/**
 * Bitácora de actividad del super administrador.
 */
class ActividadController extends Controller
{
    public function index(Request $request)
    {
        $accion = $request->get('accion');
        $q = $request->get('q');

        $logs = ActividadLog::with(['usuario', 'empresa'])
            ->when($accion, fn ($query, $v) => $query->where('accion', $v))
            ->when($q, fn ($query, $v) => $query->where('descripcion', 'like', "%{$v}%"))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $acciones = ActividadLog::select('accion')->distinct()->orderBy('accion')->pluck('accion');

        return view('admin.actividad.index', compact('logs', 'acciones', 'accion', 'q'));
    }
}
