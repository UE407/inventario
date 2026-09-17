<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\Facturacion\FacturacionManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{

    /* ============ Pantalla POS ============ */
    public function pos()
    {
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get();

        return view('ventas.pos', compact('clientes'));
    }

    /* ============ Pantalla Venta Backoffice / Directa ============ */
    public function create()
    {
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get();
        $productos = Producto::where('activo', true)
            ->with(['variantes', 'lotes' => fn ($l) => $l->fefoOrder()])
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'precio_venta', 'stock', 'unidad', 'es_perecedero', 'tiene_empaque', 'nombre_empaque', 'cant_por_empaque', 'precio_venta_empaque', 'tiene_variantes']);

        return view('ventas.create', compact('clientes', 'productos'));
    }

    /* ============ Búsqueda de productos (JSON para el POS) ============ */
    public function buscarProductos(Request $request)
    {
        $q = trim($request->get('q', ''));

        $productos = Producto::where('activo', true)
            ->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%");
            })
            ->with(['variantes', 'lotes' => fn ($l) => $l->fefoOrder()])
            ->orderBy('nombre')
            ->limit(24)
            ->get(['id', 'codigo', 'nombre', 'precio_venta', 'stock', 'stock_minimo', 'unidad', 'es_perecedero', 'tiene_empaque', 'nombre_empaque', 'cant_por_empaque', 'precio_venta_empaque', 'tiene_variantes']);

        return response()->json($productos);
    }

    /* ============ Registrar venta ============ */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'tipo_comprobante' => ['required', 'in:TICKET,BOLETA,FACTURA'],
            'metodo_pago' => ['required', 'in:EFECTIVO,TARJETA,TRANSFERENCIA,YAPE'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'efectivo_recibido' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.producto_variante_id' => ['nullable', 'exists:producto_variantes,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio' => ['nullable', 'numeric', 'min:0'],
            'items.*.lote_id' => ['nullable', 'exists:lotes,id'],
        ], [
            'items.required' => 'Agrega al menos un producto al carrito.',
            'items.min' => 'Agrega al menos un producto al carrito.',
        ]);

        try {
            $venta = DB::transaction(function () use ($data) {
                // Bloquea los productos involucrados para validar stock de forma segura
                $ids = collect($data['items'])->pluck('producto_id');
                $productos = Producto::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

                $subtotal = 0;
                $lineas = [];
                $loteService = app(\App\Services\Inventario\LoteService::class);

                foreach ($data['items'] as $item) {
                    $prod = $productos[$item['producto_id']];
                    $cant = (int) $item['cantidad'];
                    $precioUnit = (isset($item['precio']) && is_numeric($item['precio']) && (float)$item['precio'] >= 0)
                        ? round((float)$item['precio'], 2)
                        : $prod->precio_venta;

                    $varianteId = $item['producto_variante_id'] ?? null;
                    $varianteObj = null;

                    if ($varianteId) {
                        $varianteObj = \App\Models\ProductoVariante::find($varianteId);
                        if ($varianteObj && $cant > $varianteObj->stock) {
                            throw new \RuntimeException("Stock insuficiente en el sabor \"{$varianteObj->sabor}\" de {$prod->nombre} (Disponible: {$varianteObj->stock}).");
                        }
                    } else if ($cant > $prod->stock) {
                        throw new \RuntimeException("Stock insuficiente de \"{$prod->nombre}\" (disponible: {$prod->stock}).");
                    }

                    // Si el producto es perecedero, verificar stock disponible en lotes vigentes
                    $loteChunks = collect();
                    if ($prod->es_perecedero) {
                        $loteChunks = $loteService->descontarStockFEFO($prod, $cant, $item['lote_id'] ?? null);
                        $totalDescontado = $loteChunks->sum('cantidad');

                        if ($totalDescontado < $cant) {
                            throw new \RuntimeException("Stock en lotes vigentes insuficiente para \"{$prod->nombre}\" (Requerido: {$cant}, Disponible en lotes: {$totalDescontado}).");
                        }
                    }

                    $lineaSub = round($cant * $precioUnit, 2);
                    $subtotal += $lineaSub;

                    $lineas[] = [
                        'producto' => $prod,
                        'variante' => $varianteObj,
                        'cantidad' => $cant,
                        'precio' => $precioUnit,
                        'subtotal' => $lineaSub,
                        'lote_chunks' => $loteChunks,
                    ];
                }

                $descuento = round((float) ($data['descuento'] ?? 0), 2);
                $descuento = min($descuento, $subtotal);
                $total = round($subtotal - $descuento, 2);

                // En Guatemala los precios ya incluyen el IVA (12% u opcional 0%)
                $tasaIgv = Empresa::actual()->tasaIgv();
                if ($tasaIgv > 0) {
                    $baseImponible = round($total / (1 + $tasaIgv), 2);
                    $impuesto = round($total - $baseImponible, 2);
                } else {
                    $baseImponible = $total;
                    $impuesto = 0.00;
                }

                // Efectivo recibido y vuelto (solo para pago en efectivo).
                $efectivoRecibido = null;
                $vuelto = null;
                if ($data['metodo_pago'] === 'EFECTIVO' && isset($data['efectivo_recibido'])) {
                    $efectivoRecibido = round((float) $data['efectivo_recibido'], 2);
                    if ($efectivoRecibido < $total) {
                        throw new \RuntimeException('El efectivo recibido es menor que el total a pagar.');
                    }
                    $vuelto = round($efectivoRecibido - $total, 2);
                }

                $venta = Venta::create([
                    'numero' => $this->siguienteNumero(),
                    'cliente_id' => $data['cliente_id'] ?? null,
                    'user_id' => Auth::id(),
                    'tipo_comprobante' => $data['tipo_comprobante'],
                    'metodo_pago' => $data['metodo_pago'],
                    'subtotal' => $subtotal,
                    'descuento' => $descuento,
                    'impuesto' => $impuesto,
                    'total' => $total,
                    'efectivo_recibido' => $efectivoRecibido,
                    'vuelto' => $vuelto,
                    'estado' => 'COMPLETADA',
                ]);

                foreach ($lineas as $linea) {
                    $prod = $linea['producto'];
                    $variante = $linea['variante'] ?? null;
                    $descProd = $prod->nombre . ($variante ? " (Sabor: {$variante->sabor})" : '');

                    if ($variante) {
                        $variante->decrement('stock', $linea['cantidad']);
                    }

                    if ($prod->es_perecedero && $linea['lote_chunks']->isNotEmpty()) {
                        foreach ($linea['lote_chunks'] as $chunk) {
                            $lote = $chunk['lote'];
                            $cantChunk = $chunk['cantidad'];
                            $subChunk = round($cantChunk * $linea['precio'], 2);

                            VentaDetalle::create([
                                'venta_id' => $venta->id,
                                'producto_id' => $prod->id,
                                'producto_variante_id' => $variante?->id,
                                'lote_id' => $lote->id,
                                'descripcion' => $descProd . " (Lote: {$lote->codigo_lote} - Vence: {$lote->fecha_vencimiento->format('d/m/Y')})",
                                'cantidad' => $cantChunk,
                                'precio' => $linea['precio'],
                                'subtotal' => $subChunk,
                            ]);

                            MovimientoInventario::create([
                                'producto_id' => $prod->id,
                                'producto_variante_id' => $variante?->id,
                                'lote_id' => $lote->id,
                                'user_id' => Auth::id(),
                                'tipo' => 'SALIDA',
                                'motivo' => 'VENTA',
                                'cantidad' => $cantChunk,
                                'stock_anterior' => $prod->stock,
                                'stock_nuevo' => $prod->stock - $cantChunk,
                                'referencia_type' => Venta::class,
                                'referencia_id' => $venta->id,
                            ]);
                        }
                    } else {
                        VentaDetalle::create([
                            'venta_id' => $venta->id,
                            'producto_id' => $prod->id,
                            'producto_variante_id' => $variante?->id,
                            'lote_id' => null,
                            'descripcion' => $descProd,
                            'cantidad' => $linea['cantidad'],
                            'precio' => $linea['precio'],
                            'subtotal' => $linea['subtotal'],
                        ]);

                        $stockAnterior = $prod->stock;

                        MovimientoInventario::create([
                            'producto_id' => $prod->id,
                            'producto_variante_id' => $variante?->id,
                            'lote_id' => null,
                            'user_id' => Auth::id(),
                            'tipo' => 'SALIDA',
                            'motivo' => 'VENTA',
                            'cantidad' => $linea['cantidad'],
                            'stock_anterior' => $stockAnterior,
                            'stock_nuevo' => $stockAnterior - $linea['cantidad'],
                            'referencia_type' => Venta::class,
                            'referencia_id' => $venta->id,
                        ]);
                    }

                    if ($prod->tiene_variantes) {
                        $prod->update(['stock' => $prod->variantes()->sum('stock')]);
                    } else {
                        $prod->decrement('stock', $linea['cantidad']);
                    }
                }

                return $venta;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // ---- Facturación electrónica ----
        $feResultado = app(FacturacionManager::class)->emitirParaVenta($venta);

        return response()->json([
            'message' => 'Venta registrada correctamente.',
            'venta_id' => $venta->id,
            'fe_estado' => $venta->fe_estado,
            'fe_mensaje' => $feResultado->mensaje,
            'redirect' => route('ventas.show', $venta),
        ]);
    }

    /* ============ Historial de ventas ============ */
    public function index(Request $request)
    {
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');
        $estado = $request->get('estado');
        $q = $request->get('q');

        $ventas = Venta::with(['cliente', 'usuario'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('numero', 'like', "%{$q}%")
                        ->orWhereHas('cliente', function ($c) use ($q) {
                            $c->where('nombre', 'like', "%{$q}%")
                              ->orWhere('numero_documento', 'like', "%{$q}%");
                        });
                });
            })
            ->when($desde, fn ($query) => $query->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('created_at', '<=', $hasta))
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('ventas.index', compact('ventas', 'desde', 'hasta', 'estado', 'q'));
    }

    /* ============ Ver / Ticket ============ */
    public function show(Venta $venta)
    {
        $venta->load(['detalles.lote', 'cliente', 'usuario']);

        return view('ventas.show', compact('venta'));
    }

    /* ============ Anular venta (repone stock) ============ */
    public function anular(Request $request, Venta $venta)
    {
        if ($venta->estado === 'ANULADA') {
            return back()->with('error', 'La venta ya estaba anulada.');
        }

        $motivo = trim((string) $request->input('motivo', 'ANULACION DE LA OPERACION'));

        DB::transaction(function () use ($venta) {
            foreach ($venta->detalles as $detalle) {
                $prod = Producto::find($detalle->producto_id);
                if (! $prod) {
                    continue;
                }

                $stockAnterior = $prod->stock;
                $prod->increment('stock', $detalle->cantidad);

                if ($detalle->lote_id && $detalle->lote) {
                    $lote = $detalle->lote;
                    $lote->increment('stock_actual', $detalle->cantidad);
                    $lote->update(['estado' => $lote->estado_calculado]);
                }

                MovimientoInventario::create([
                    'producto_id' => $prod->id,
                    'lote_id' => $detalle->lote_id,
                    'user_id' => Auth::id(),
                    'tipo' => 'ENTRADA',
                    'motivo' => 'ANULACION_VENTA',
                    'cantidad' => $detalle->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockAnterior + $detalle->cantidad,
                    'referencia_type' => Venta::class,
                    'referencia_id' => $venta->id,
                ]);
            }

            $venta->update(['estado' => 'ANULADA']);
        });

        // Anulación electrónica ante SUNAT (baja / nota de crédito) si el
        // comprobante fue emitido. No interrumpe el flujo si falla.
        $mensaje = "Venta {$venta->numero} anulada y stock repuesto.";
        if ($venta->feEmitido()) {
            $r = app(FacturacionManager::class)->anularVenta($venta, $motivo);
            $mensaje .= ' ' . $r->mensaje;
        }

        return back()->with('success', $mensaje);
    }

    /* ============ Helpers ============ */
    private function siguienteNumero(): string
    {
        $ultimo = Venta::where('numero', 'like', 'V-%')
            ->orderByDesc('id')
            ->value('numero');

        $n = $ultimo ? ((int) substr($ultimo, 2)) + 1 : 1;

        return 'V-' . str_pad($n, 6, '0', STR_PAD_LEFT);
    }
}
