<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SuscripcionController extends Controller
{
    /** Página de suscripción: plan actual, uso vs límites y planes disponibles. */
    public function index()
    {
        $empresa = Empresa::actual()->load('plan');
        $planes = Plan::where('activo', true)->orderBy('orden')->get();

        // Uso actual de los recursos con límite.
        $uso = [
            'productos' => $empresa->uso('productos'),
            'usuarios' => $empresa->uso('usuarios'),
            'ventas_mes' => $empresa->uso('ventas_mes'),
        ];

        return view('suscripcion.index', compact('empresa', 'planes', 'uso'));
    }

    /**
     * Cambia el plan del tenant. Activación manual (marcador de posición para
     * una pasarela de pago real: Stripe, MercadoPago o Culqi).
     */
    public function cambiar(Request $request)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:planes,id'],
        ], [
            'plan_id.required' => 'Selecciona un plan.',
            'plan_id.exists' => 'El plan seleccionado no existe.',
        ]);

        $empresa = Empresa::actual();
        $plan = Plan::findOrFail($data['plan_id']);

        // No permitir bajar a un plan cuyos límites ya se superan.
        foreach (['productos', 'usuarios'] as $recurso) {
            $limite = $plan->limite($recurso);
            if ($limite !== null && $empresa->uso($recurso) > $limite) {
                return back()->with('error',
                    "No puedes cambiar al plan {$plan->nombre}: tienes {$empresa->uso($recurso)} {$recurso} y el plan permite {$limite}.");
            }
        }

        DB::transaction(function () use ($empresa, $plan) {
            $inicia = Carbon::today();
            // Plan gratis: activa sin vencimiento. De pago: 1 mes (simulado).
            $termina = $plan->esGratis() ? null : $inicia->copy()->addMonth();

            $empresa->update([
                'plan_id' => $plan->id,
                'estado_suscripcion' => 'activa',
                'suscripcion_termina_en' => $termina,
            ]);

            Suscripcion::create([
                'empresa_id' => $empresa->id,
                'plan_id' => $plan->id,
                'estado' => 'activa',
                'monto' => $plan->precio,
                'inicia_en' => $inicia,
                'termina_en' => $termina,
            ]);
        });

        return redirect()->route('suscripcion.index')
            ->with('success', "Plan actualizado a {$plan->nombre}.");
    }
}
