<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\Producto;
use App\Services\Inventario\LoteService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LoteController extends Controller
{
    public function index(Request $request, LoteService $loteService)
    {
        $loteService->actualizarEstadosLotes();

        $q = $request->get('q');
        $estado = $request->get('estado');
        $productoId = $request->get('producto_id');

        $lotes = Lote::with('producto')
            ->when($q, function ($query) use ($q) {
                $query->where('codigo_lote', 'like', "%{$q}%")
                    ->orWhereHas('producto', fn ($p) => $p->where('nombre', 'like', "%{$q}%"));
            })
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($productoId, fn ($query) => $query->where('producto_id', $productoId))
            ->orderBy('fecha_vencimiento', 'asc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'vigentes' => Lote::where('estado', 'VIGENTE')->count(),
            'por_vencer' => Lote::where('estado', 'POR_VENCER')->count(),
            'vencidos' => Lote::where('estado', 'VENCIDO')->count(),
            'agotados' => Lote::where('estado', 'AGOTADO')->count(),
        ];

        $productos = Producto::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        return view('lotes.index', compact('lotes', 'stats', 'productos', 'q', 'estado', 'productoId'));
    }

    public function store(Request $request, LoteService $loteService)
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'codigo_lote' => ['required', 'string', 'max:50'],
            'fecha_vencimiento' => ['required', 'date'],
            'stock_inicial' => ['required', 'integer', 'min:1'],
        ]);

        $prod = Producto::findOrFail($data['producto_id']);

        if (!$prod->es_perecedero) {
            $prod->update(['es_perecedero' => true]);
        }

        $lote = $loteService->registrarLoteCompra(
            $prod,
            $data['codigo_lote'],
            $data['fecha_vencimiento'],
            $data['stock_inicial']
        );

        // Opcional: actualizar stock del producto si no vino de una compra
        $prod->increment('stock', $data['stock_inicial']);

        return back()->with('success', "Lote {$lote->codigo_lote} registrado correctamente.");
    }
}
