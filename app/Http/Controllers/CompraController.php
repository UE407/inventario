<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $estado = $request->get('estado');

        $compras = Compra::with(['proveedor', 'usuario'])
            ->when($q, fn ($query) => $query->where('numero', 'like', "%{$q}%"))
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('compras.index', compact('compras', 'q', 'estado'));
    }

    public function create()
    {
        return view('compras.create', [
            'proveedores' => Proveedor::where('activo', true)->orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->with('variantes')->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'precio_compra', 'stock', 'unidad', 'es_perecedero', 'tiene_empaque', 'nombre_empaque', 'cant_por_empaque', 'precio_venta_empaque', 'tiene_variantes']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'fecha' => ['required', 'date'],
            'observacion' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.producto_variante_id' => ['nullable', 'exists:producto_variantes,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio' => ['required', 'numeric', 'min:0'],
            'items.*.codigo_lote' => ['nullable', 'string', 'max:50'],
            'items.*.fecha_vencimiento' => ['nullable', 'date'],
        ], [
            'items.required' => 'Agrega al menos un producto a la compra.',
            'items.min' => 'Agrega al menos un producto a la compra.',
        ]);

        $compra = DB::transaction(function () use ($data) {
            $total = 0;
            foreach ($data['items'] as $item) {
                $total += round($item['cantidad'] * $item['precio'], 2);
            }

            $tasaIgv = Empresa::actual()->tasaIgv();
            if ($tasaIgv > 0) {
                $subtotal = round($total / (1 + $tasaIgv), 2);
                $impuesto = round($total - $subtotal, 2);
            } else {
                $subtotal = $total;
                $impuesto = 0.00;
            }

            $compra = Compra::create([
                'numero' => $this->siguienteNumero(),
                'proveedor_id' => $data['proveedor_id'],
                'user_id' => Auth::id(),
                'fecha' => $data['fecha'],
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
                'estado' => 'RECIBIDA',
                'observacion' => $data['observacion'] ?? null,
            ]);

            $loteService = app(\App\Services\Inventario\LoteService::class);

            foreach ($data['items'] as $item) {
                $prod = Producto::lockForUpdate()->find($item['producto_id']);
                $cant = (int) $item['cantidad'];
                $precio = round((float) $item['precio'], 2);
                $varianteId = $item['producto_variante_id'] ?? null;

                $loteId = null;
                $codigoLote = trim($item['codigo_lote'] ?? '');
                $fechaVenc = trim($item['fecha_vencimiento'] ?? '');

                if (!empty($codigoLote) || !empty($fechaVenc) || $prod->es_perecedero) {
                    if (empty($codigoLote)) {
                        $codigoLote = 'LOTE-' . date('Ymd') . '-' . rand(100, 999);
                    }
                    if (empty($fechaVenc)) {
                        $fechaVenc = now()->addYear()->format('Y-m-d');
                    }

                    if (! $prod->es_perecedero) {
                        $prod->update(['es_perecedero' => true]);
                    }

                    $lote = $loteService->registrarLoteCompra(
                        $prod,
                        $codigoLote,
                        $fechaVenc,
                        $cant
                    );
                    $loteId = $lote->id;
                }

                if ($varianteId) {
                    $variante = \App\Models\ProductoVariante::find($varianteId);
                    if ($variante) {
                        $variante->increment('stock', $cant);
                    }
                }

                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $prod->id,
                    'producto_variante_id' => $varianteId,
                    'lote_id' => $loteId,
                    'cantidad' => $cant,
                    'precio' => $precio,
                    'subtotal' => round($cant * $precio, 2),
                ]);

                $stockAnterior = $prod->stock;
                if ($prod->tiene_variantes) {
                    $prod->update([
                        'stock' => $prod->variantes()->sum('stock'),
                        'precio_compra' => $precio,
                    ]);
                } else {
                    $prod->increment('stock', $cant);
                    $prod->update(['precio_compra' => $precio]);
                }

                MovimientoInventario::create([
                    'producto_id' => $prod->id,
                    'producto_variante_id' => $varianteId,
                    'lote_id' => $loteId,
                    'user_id' => Auth::id(),
                    'tipo' => 'ENTRADA',
                    'motivo' => 'COMPRA',
                    'cantidad' => $cant,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $prod->stock,
                    'referencia_type' => Compra::class,
                    'referencia_id' => $compra->id,
                ]);
            }

            return $compra;
        });

        return redirect()->route('compras.show', $compra)
            ->with('success', "Compra {$compra->numero} registrada. Stock y lotes actualizados.");
    }

    public function show(Compra $compra)
    {
        $compra->load(['detalles.producto', 'detalles.lote', 'proveedor', 'usuario']);

        return view('compras.show', compact('compra'));
    }

    public function destroy(Compra $compra)
    {
        // Eliminar una compra recibida repondría inconsistencias; se prefiere anular.
        return $this->anular($compra);
    }

    public function anular(Compra $compra)
    {
        if ($compra->estado === 'ANULADA') {
            return back()->with('error', 'La compra ya estaba anulada.');
        }

        DB::transaction(function () use ($compra) {
            foreach ($compra->detalles as $detalle) {
                $prod = Producto::lockForUpdate()->find($detalle->producto_id);
                if (! $prod) {
                    continue;
                }

                $stockAnterior = $prod->stock;
                // Descuenta lo que había entrado (sin dejar negativo)
                $descontar = min($detalle->cantidad, $prod->stock);
                $prod->decrement('stock', $descontar);

                if ($detalle->lote_id && $detalle->lote) {
                    $lote = $detalle->lote;
                    $lote->decrement('stock_actual', min($descontar, $lote->stock_actual));
                    $lote->update(['estado' => $lote->estado_calculado]);
                }

                MovimientoInventario::create([
                    'producto_id' => $prod->id,
                    'lote_id' => $detalle->lote_id,
                    'user_id' => Auth::id(),
                    'tipo' => 'SALIDA',
                    'motivo' => 'ANULACION_COMPRA',
                    'cantidad' => $descontar,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockAnterior - $descontar,
                    'referencia_type' => Compra::class,
                    'referencia_id' => $compra->id,
                ]);
            }

            $compra->update(['estado' => 'ANULADA']);
        });

        return back()->with('success', "Compra {$compra->numero} anulada y stock ajustado.");
    }

    private function siguienteNumero(): string
    {
        $ultimo = Compra::where('numero', 'like', 'C-%')
            ->orderByDesc('id')
            ->value('numero');

        $n = $ultimo ? ((int) substr($ultimo, 2)) + 1 : 1;

        return 'C-' . str_pad($n, 6, '0', STR_PAD_LEFT);
    }
}
