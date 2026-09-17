<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActividadLog;
use App\Models\PlataformaConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Configuración global de la plataforma (super admin).
 */
class ConfiguracionController extends Controller
{
    public function edit()
    {
        return view('admin.configuracion', ['config' => PlataformaConfig::actual()]);
    }

    public function update(Request $request)
    {
        $config = PlataformaConfig::actual();

        $data = $request->validate([
            'nombre_saas' => ['required', 'string', 'max:100'],
            'dias_trial' => ['required', 'integer', 'min:0', 'max:365'],
            'correo_soporte' => ['nullable', 'email', 'max:150'],
            'moneda' => ['required', 'string', 'max:5'],
            'mensaje_bienvenida' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ], [
            'nombre_saas.required' => 'El nombre de la plataforma es obligatorio.',
            'dias_trial.required' => 'Indica los días de prueba.',
        ]);

        if ($request->hasFile('logo')) {
            if ($config->logo) {
                Storage::disk('public')->delete($config->logo);
            }
            $data['logo'] = $request->file('logo')->store('plataforma', 'public');
        }

        $config->update($data);

        ActividadLog::registrar('configuracion', 'Actualizó la configuración de la plataforma');

        return back()->with('success', 'Configuración de la plataforma actualizada.');
    }
}
