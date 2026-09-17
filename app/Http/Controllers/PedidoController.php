<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Services\Ventas\PedidoService;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $estado = $request->get('estado');
        $vendedorId = $request->get('vendedor_id');
        $rutaId = $request->get('ruta_id');
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        $pedidos = Pedido::visiblesParaUsuario()
            ->with(['cliente', 'vendedor', 'venta', 'ruta'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('numero', 'like', "%{$q}%")
                        ->orWhereHas('cliente', function ($c) use ($q) {
                            $c->where('nombre', 'like', "%{$q}%")
                              ->orWhere('numero_documento', 'like', "%{$q}%");
                        });
                });
            })
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($vendedorId, fn ($query) => $query->where('user_id', $vendedorId))
            ->when($rutaId, fn ($query) => $query->where('ruta_id', $rutaId))
            ->when($desde, fn ($query) => $query->whereDate('fecha_pedido', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('fecha_pedido', '<=', $hasta))
            ->latest('fecha_pedido')
            ->paginate(12)
            ->withQueryString();

        $vendedores = User::where('activo', true)->orderBy('name')->get();
        $rutas = \App\Models\Ruta::where('activo', true)->orderBy('nombre')->get();

        $stats = [
            'pendientes' => Pedido::visiblesParaUsuario()->where('estado', 'PENDIENTE')->count(),
            'convertidos' => Pedido::visiblesParaUsuario()->where('estado', 'CONVERTIDO')->count(),
            'cancelados' => Pedido::visiblesParaUsuario()->where('estado', 'CANCELADO')->count(),
            'total' => Pedido::visiblesParaUsuario()->count(),
        ];

        return view('pedidos.index', compact('pedidos', 'vendedores', 'rutas', 'stats', 'q', 'estado', 'vendedorId', 'rutaId', 'desde', 'hasta'));
    }

    public function create()
    {
        $clientes = Cliente::where('activo', true)->with('ruta')->orderBy('nombre')->get();
        $rutas = \App\Models\Ruta::where('activo', true)->orderBy('nombre')->get();
        $productos = Producto::where('activo', true)
            ->with(['variantes', 'lotes' => fn ($l) => $l->fefoOrder()])
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'precio_venta', 'stock', 'unidad', 'es_perecedero', 'tiene_empaque', 'nombre_empaque', 'cant_por_empaque', 'precio_venta_empaque', 'tiene_variantes']);

        return view('pedidos.create', compact('clientes', 'rutas', 'productos'));
    }

    public function store(Request $request, PedidoService $pedidoService)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'ruta_id' => ['nullable', 'exists:rutas,id'],
            'fecha_entrega_estimada' => ['nullable', 'date'],
            'observacion' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio' => ['nullable', 'numeric', 'min:0'],
            'items.*.lote_id' => ['nullable', 'exists:lotes,id'],
        ], [
            'items.required' => 'Agrega al menos un producto al pedido.',
            'items.min' => 'Agrega al menos un producto al pedido.',
        ]);

        try {
            $pedido = $pedidoService->crearPedido($data);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Pedido registrado y stock reservado correctamente.',
                    'pedido_id' => $pedido->id,
                    'redirect' => route('pedidos.show', $pedido),
                ]);
            }

            return redirect()->route('pedidos.show', $pedido)
                ->with('success', "Pedido {$pedido->numero} registrado correctamente. Stock reservado.");
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Pedido $pedido)
    {
        // Verificar vendedor aislamiento
        if (auth()->user()->rol === 'vendedor' && $pedido->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para ver este pedido.');
        }

        $pedido->load(['cliente', 'vendedor', 'venta', 'detalles.producto', 'detalles.lote']);
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get();

        return view('pedidos.show', compact('pedido', 'clientes'));
    }

    public function convertir(Request $request, Pedido $pedido, PedidoService $pedidoService)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'tipo_comprobante' => ['required', 'in:TICKET,BOLETA,FACTURA'],
            'metodo_pago' => ['required', 'in:CONTRA_ENTREGA,CREDITO,EFECTIVO,TARJETA,TRANSFERENCIA,YAPE'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'efectivo_recibido' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $venta = $pedidoService->convertirAVenta($pedido, $data);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Pedido convertido a Venta correctamente.',
                    'venta_id' => $venta->id,
                    'redirect' => route('ventas.show', $venta),
                ]);
            }

            return redirect()->route('ventas.show', $venta)
                ->with('success', "Pedido {$pedido->numero} convertido a Venta {$venta->numero}.");
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelar(Pedido $pedido, PedidoService $pedidoService)
    {
        if (auth()->user()->rol === 'vendedor' && $pedido->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para modificar este pedido.');
        }

        try {
            $pedidoService->cancelarPedido($pedido);

            return back()->with('success', "Pedido {$pedido->numero} cancelado y stock liberado.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
