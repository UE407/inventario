<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActividadLog;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Gestión de tenants (empresas) por el super administrador.
 */
class TenantController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $estado = $request->get('estado');

        $tenants = Empresa::with('plan')
            ->withCount(['usuarios', 'productos', 'ventas'])
            ->when($q, fn ($query) => $query->where('nombre', 'like', "%{$q}%")->orWhere('ruc', 'like', "%{$q}%"))
            ->when($estado, fn ($query) => $query->where('estado_suscripcion', $estado))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tenants.index', compact('tenants', 'q', 'estado'));
    }

    public function show(Empresa $tenant)
    {
        $tenant->load('plan');
        $planes = Plan::where('activo', true)->orderBy('orden')->get();

        $uso = [
            'productos' => $tenant->uso('productos'),
            'usuarios' => $tenant->uso('usuarios'),
            'ventas_mes' => $tenant->uso('ventas_mes'),
        ];

        $usuarios = User::where('empresa_id', $tenant->id)->orderBy('name')->get();
        $suscripciones = Suscripcion::with('plan')
            ->where('empresa_id', $tenant->id)->latest()->get();

        return view('admin.tenants.show', compact('tenant', 'planes', 'uso', 'usuarios', 'suscripciones'));
    }

    public function suspender(Empresa $tenant)
    {
        $tenant->suspender();
        ActividadLog::registrar('suspender', "Suspendió a «{$tenant->nombre}»", $tenant->id);
        return back()->with('success', "Tenant «{$tenant->nombre}» suspendido.");
    }

    public function activar(Empresa $tenant)
    {
        $tenant->reactivar();
        ActividadLog::registrar('activar', "Reactivó a «{$tenant->nombre}»", $tenant->id);
        return back()->with('success', "Tenant «{$tenant->nombre}» reactivado.");
    }

    public function cambiarPlan(Request $request, Empresa $tenant)
    {
        $data = $request->validate(['plan_id' => ['required', 'exists:planes,id']]);
        $plan = Plan::findOrFail($data['plan_id']);

        DB::transaction(function () use ($tenant, $plan) {
            $termina = $plan->esGratis() ? null : Carbon::today()->addMonth();
            $tenant->update([
                'plan_id' => $plan->id,
                'estado_suscripcion' => 'activa',
                'suscripcion_termina_en' => $termina,
            ]);
            Suscripcion::create([
                'empresa_id' => $tenant->id, 'plan_id' => $plan->id,
                'estado' => 'activa', 'monto' => $plan->precio,
                'inicia_en' => Carbon::today(), 'termina_en' => $termina,
            ]);
        });

        ActividadLog::registrar('cambiar_plan', "Cambió el plan de «{$tenant->nombre}» a {$plan->nombre}", $tenant->id);

        return back()->with('success', "Plan de «{$tenant->nombre}» cambiado a {$plan->nombre}.");
    }

    public function destroy(Empresa $tenant)
    {
        // Purga todos los datos del tenant y luego la empresa.
        Tenant::withTenant($tenant->id, function () {
            \App\Models\VentaDetalle::query()->delete();
            \App\Models\CompraDetalle::query()->delete();
            \App\Models\MovimientoInventario::query()->delete();
            \App\Models\Venta::query()->delete();
            \App\Models\Compra::query()->delete();
            \App\Models\Producto::query()->delete();
            \App\Models\Categoria::query()->delete();
            \App\Models\Marca::query()->delete();
            \App\Models\Cliente::query()->delete();
            \App\Models\Proveedor::query()->delete();
        });

        User::where('empresa_id', $tenant->id)->delete();
        Suscripcion::where('empresa_id', $tenant->id)->delete();
        $nombreTenant = $tenant->nombre;
        $tenant->delete();

        ActividadLog::registrar('eliminar', "Eliminó el tenant «{$nombreTenant}» y todos sus datos");

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant eliminado con todos sus datos.');
    }

    /** Inicia sesión como el administrador del tenant (soporte). */
    public function impersonar(Request $request, Empresa $tenant)
    {
        $admin = User::where('empresa_id', $tenant->id)
            ->where('rol', 'admin')->where('activo', true)->first()
            ?? User::where('empresa_id', $tenant->id)->where('activo', true)->first();

        if (! $admin) {
            return back()->with('error', 'Este tenant no tiene usuarios activos para impersonar.');
        }

        // Recuerda al super admin para poder volver.
        $request->session()->put('impersonator_id', Auth::id());
        Auth::login($admin);
        ActividadLog::registrar('impersonar', "Ingresó como «{$tenant->nombre}»", $tenant->id);

        return redirect()->route('dashboard')
            ->with('success', "Ahora ves el sistema como «{$tenant->nombre}».");
    }
}
