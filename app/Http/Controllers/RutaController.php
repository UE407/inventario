<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use Illuminate\Http\Request;

class RutaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $rutas = Ruta::when($q, function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                      ->orWhere('codigo', 'like', "%{$q}%");
            })
            ->withCount(['clientes', 'pedidos'])
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return view('rutas.index', compact('rutas', 'q'));
    }

    public function create()
    {
        return view('rutas.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'dias_entrega_estimados' => ['required', 'integer', 'min:0', 'max:30'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->has('activo');

        Ruta::create($data);

        return redirect()->route('rutas.index')
            ->with('success', 'Ruta de entrega registrada exitosamente.');
    }

    public function edit(Ruta $ruta)
    {
        return view('rutas.edit', compact('ruta'));
    }

    public function update(Request $request, Ruta $ruta)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'dias_entrega_estimados' => ['required', 'integer', 'min:0', 'max:30'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->has('activo');

        $ruta->update($data);

        return redirect()->route('rutas.index')
            ->with('success', 'Ruta de entrega actualizada correctamente.');
    }

    public function destroy(Ruta $ruta)
    {
        if ($ruta->pedidos()->exists()) {
            return back()->with('error', 'No se puede eliminar esta ruta porque tiene pedidos asociados.');
        }

        $ruta->delete();

        return redirect()->route('rutas.index')
            ->with('success', 'Ruta eliminada correctamente.');
    }
}
