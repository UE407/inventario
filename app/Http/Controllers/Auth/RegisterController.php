<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\PlataformaConfig;
use App\Models\Suscripcion;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /** Días de prueba gratis al registrar un negocio nuevo. */
    private const DIAS_TRIAL = 14;

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'empresa' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'empresa.required' => 'El nombre del negocio es obligatorio.',
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        // Crea el tenant (empresa) con prueba gratis y su administrador.
        $user = DB::transaction(function () use ($data) {
            // Plan de prueba: el de mayor nivel disponible (o ninguno si no hay).
            $plan = Plan::where('activo', true)->orderByDesc('orden')->first();
            $diasTrial = PlataformaConfig::actual()->dias_trial;
            $trialTermina = Carbon::today()->addDays($diasTrial);

            $empresa = Empresa::create([
                'nombre' => $data['empresa'],
                'email' => $data['email'],
                'plan_id' => $plan?->id,
                'estado_suscripcion' => 'trial',
                'trial_termina_en' => $trialTermina,
            ]);

            if ($plan) {
                Suscripcion::create([
                    'empresa_id' => $empresa->id,
                    'plan_id' => $plan->id,
                    'estado' => 'trial',
                    'monto' => 0,
                    'inicia_en' => Carbon::today(),
                    'termina_en' => $trialTermina,
                ]);
            }

            // Fija el tenant para que el usuario se cree ya asociado a la empresa.
            Tenant::set($empresa->id);

            return User::create([
                'empresa_id' => $empresa->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed por el cast
                'rol' => 'admin', // el primer registro es el dueño del negocio
                'activo' => true,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
