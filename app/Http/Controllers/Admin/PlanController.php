<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActividadLog;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD de planes (catálogo de suscripción) para el super administrador.
 */
class PlanController extends Controller
{
    public function index()
    {
        $planes = Plan::withCount('empresas')->orderBy('orden')->get();
        return view('admin.planes.index', compact('planes'));
    }

    public function create()
    {
        return view('admin.planes.form', ['plan' => new Plan(['activo' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $data['activo'] = $request->boolean('activo');
        $plan = Plan::create($data);
        ActividadLog::registrar('plan_crear', "Creó el plan «{$plan->nombre}»");
        return redirect()->route('admin.planes.index')->with('success', 'Plan creado.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.planes.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validar($request, $plan->id);
        $data['activo'] = $request->boolean('activo');
        $plan->update($data);
        ActividadLog::registrar('plan_editar', "Editó el plan «{$plan->nombre}»");
        return redirect()->route('admin.planes.index')->with('success', 'Plan actualizado.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->empresas()->exists()) {
            return back()->with('error', 'No puedes eliminar un plan que tiene tenants asignados.');
        }
        $nombrePlan = $plan->nombre;
        $plan->delete();
        ActividadLog::registrar('plan_eliminar', "Eliminó el plan «{$nombrePlan}»");
        return back()->with('success', 'Plan eliminado.');
    }

    private function validar(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('planes', 'slug')->ignore($id)],
            'precio' => ['required', 'numeric', 'min:0'],
            'limite_productos' => ['nullable', 'integer', 'min:0'],
            'limite_usuarios' => ['nullable', 'integer', 'min:0'],
            'limite_ventas_mes' => ['nullable', 'integer', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'slug.required' => 'El slug es obligatorio.',
            'slug.unique' => 'Ya existe un plan con ese slug.',
            'precio.required' => 'El precio es obligatorio.',
        ]);
    }
}
