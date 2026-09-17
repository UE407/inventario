<?php

namespace App\Services\Ventas;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\Facturacion\FacturacionManager;
use App\Services\Inventario\LoteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PedidoService
{
    /**
     * Registra un nuevo pedido de ruta y RESERVA el stock de inmediato.
     */
    public function crearPedido(array $data): Pedido
    {
        return DB::transaction(function () use ($data) {
            $ids = collect($data['items'])->pluck('producto_id');
            $productos = Producto::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            $loteService = app(LoteService::class);

            $subtotal = 0;
            $lineas = [];

            foreach ($data['items'] as $item) {
                $prod = $productos[$item['producto_id']];
                $cant = (int) $item['cantidad'];
                $precio = (float) ($item['precio'] ?? $prod->precio_venta);

                $varianteId = $item['producto_variante_id'] ?? null;
                $varianteObj = null;

                if ($varianteId) {
                    $varianteObj = \App\Models\ProductoVariante::find($varianteId);
                    if ($varianteObj && $cant > $varianteObj->stock) {
                        throw new RuntimeException("Stock insuficiente en el sabor \"{$varianteObj->sabor}\" para reservar {$prod->nombre} (Disponible: {$varianteObj->stock}).");
                    }
                } else if ($cant > $prod->stock) {
                    throw new RuntimeException("Stock insuficiente para reservar \"{$prod->nombre}\" (Disponible: {$prod->stock}).");
                }

                $loteChunks = collect();
                if ($prod->es_perecedero) {
                    $loteChunks = $loteService->descontarStockFEFO($prod, $cant, $item['lote_id'] ?? null);
                    $totalDescontado = $loteChunks->sum('cantidad');

                    if ($totalDescontado < $cant) {
                        throw new RuntimeException("Stock en lotes vigentes insuficiente para reservar \"{$prod->nombre}\" (Requerido: {$cant}, Disponible en lotes: {$totalDescontado}).");
                    }
                }

                $lineaSub = round($cant * $precio, 2);
                $subtotal += $lineaSub;

                $lineas[] = [
                    'producto' => $prod,
                    'variante' => $varianteObj,
                    'cantidad' => $cant,
                    'precio' => $precio,
                    'subtotal' => $lineaSub,
                    'lote_chunks' => $loteChunks,
                ];
            }

            $total = round($subtotal, 2);
            $tasaIgv = Empresa::actual()->tasaIgv();
            $impuesto = $tasaIgv > 0 ? round($total - ($total / (1 + $tasaIgv)), 2) : 0.00;

            $clienteId = $data['cliente_id'] ?? null;
            $rutaId = $data['ruta_id'] ?? null;

            if (!$rutaId && $clienteId) {
                $clienteObj = Cliente::find($clienteId);
                $rutaId = $clienteObj?->ruta_id;
            }

            $pedido = Pedido::create([
                'numero' => $this->siguienteNumero(),
                'cliente_id' => $clienteId,
                'ruta_id' => $rutaId,
                'user_id' => Auth::id(),
                'fecha_pedido' => now(),
                'fecha_entrega_estimada' => $data['fecha_entrega_estimada'] ?? null,
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
                'estado' => 'PENDIENTE',
                'observacion' => $data['observacion'] ?? null,
            ]);

            foreach ($lineas as $linea) {
                $prod = $linea['producto'];
                $variante = $linea['variante'] ?? null;

                if ($variante) {
                    $variante->decrement('stock', $linea['cantidad']);
                }

                if ($prod->es_perecedero && $linea['lote_chunks']->isNotEmpty()) {
                    foreach ($linea['lote_chunks'] as $chunk) {
                        $lote = $chunk['lote'];
                        $cantChunk = $chunk['cantidad'];
                        $subChunk = round($cantChunk * $linea['precio'], 2);

                        PedidoDetalle::create([
                            'pedido_id' => $pedido->id,
                            'producto_id' => $prod->id,
                            'producto_variante_id' => $variante?->id,
                            'lote_id' => $lote->id,
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
                            'motivo' => 'RESERVA_PEDIDO',
                            'cantidad' => $cantChunk,
                            'stock_anterior' => $prod->stock,
                            'stock_nuevo' => $prod->stock - $cantChunk,
                            'referencia_type' => Pedido::class,
                            'referencia_id' => $pedido->id,
                        ]);
                    }
                } else {
                    PedidoDetalle::create([
                        'pedido_id' => $pedido->id,
                        'producto_id' => $prod->id,
                        'producto_variante_id' => $variante?->id,
                        'lote_id' => null,
                        'cantidad' => $linea['cantidad'],
                        'precio' => $linea['precio'],
                        'subtotal' => $linea['subtotal'],
                    ]);

                    $stockAnterior = $prod->stock;

                    MovimientoInventario::create([
                        'producto_id' => $prod->id,
                        'producto_variante_id' => $variante?->id,
                        'user_id' => Auth::id(),
                        'tipo' => 'SALIDA',
                        'motivo' => 'RESERVA_PEDIDO',
                        'cantidad' => $linea['cantidad'],
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $stockAnterior - $linea['cantidad'],
                        'referencia_type' => Pedido::class,
                        'referencia_id' => $pedido->id,
                    ]);
                }

                if ($prod->tiene_variantes) {
                    $prod->update(['stock' => $prod->variantes()->sum('stock')]);
                } else {
                    $prod->decrement('stock', $linea['cantidad']);
                }
            }

            return $pedido;
        });
    }

    /**
     * Convierte un pedido pendiente en una VENTA final (emitiendo comprobante FEL SAT si corresponde).
     */
    public function convertirAVenta(Pedido $pedido, array $dataVenta): Venta
    {
        if ($pedido->estado !== 'PENDIENTE') {
            throw new RuntimeException("El pedido {$pedido->numero} ya fue procesado o cancelado.");
        }

        $venta = DB::transaction(function () use ($pedido, $dataVenta) {
            $subtotal = $pedido->subtotal;
            $descuento = round((float) ($dataVenta['descuento'] ?? 0), 2);
            $descuento = min($descuento, $subtotal);
            $total = round($subtotal - $descuento, 2);

            $tasaIgv = Empresa::actual()->tasaIgv();
            $impuesto = $tasaIgv > 0 ? round($total - ($total / (1 + $tasaIgv)), 2) : 0.00;

            $efectivoRecibido = null;
            $vuelto = null;
            $metodoPago = $dataVenta['metodo_pago'] ?? 'CONTRA_ENTREGA';
            if ($metodoPago === 'EFECTIVO' && isset($dataVenta['efectivo_recibido']) && $dataVenta['efectivo_recibido'] !== null && $dataVenta['efectivo_recibido'] !== '') {
                $efectivoRecibido = round((float) $dataVenta['efectivo_recibido'], 2);
                if ($efectivoRecibido < $total) {
                    throw new RuntimeException('El efectivo recibido es menor que el total de la venta.');
                }
                $vuelto = round($efectivoRecibido - $total, 2);
            }

            $venta = Venta::create([
                'numero' => $this->siguienteNumeroVenta(),
                'cliente_id' => $dataVenta['cliente_id'] ?? $pedido->cliente_id,
                'user_id' => Auth::id(),
                'tipo_comprobante' => $dataVenta['tipo_comprobante'] ?? 'TICKET',
                'metodo_pago' => $metodoPago,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'impuesto' => $impuesto,
                'total' => $total,
                'efectivo_recibido' => $efectivoRecibido,
                'vuelto' => $vuelto,
                'estado' => 'COMPLETADA',
            ]);

            foreach ($pedido->detalles as $det) {
                $lote = $det->lote;
                $desc = $det->producto->nombre;
                if ($lote) {
                    $desc .= " (Lote: {$lote->codigo_lote} - Vence: {$lote->fecha_vencimiento->format('d/m/Y')})";
                }

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $det->producto_id,
                    'lote_id' => $det->lote_id,
                    'descripcion' => $desc,
                    'cantidad' => $det->cantidad,
                    'precio' => $det->precio,
                    'subtotal' => $det->subtotal,
                ]);
            }

            $pedido->update([
                'estado' => 'CONVERTIDO',
                'venta_id' => $venta->id,
            ]);

            return $venta;
        });

        // Emitir facturación electrónica
        app(FacturacionManager::class)->emitirParaVenta($venta);

        return $venta;
    }

    /**
     * Cancela un pedido pendiente y LIBERA el stock previamente reservado.
     */
    public function cancelarPedido(Pedido $pedido): void
    {
        if ($pedido->estado !== 'PENDIENTE') {
            throw new RuntimeException("Solo se pueden cancelar pedidos en estado PENDIENTE.");
        }

        DB::transaction(function () use ($pedido) {
            foreach ($pedido->detalles as $det) {
                $prod = Producto::find($det->producto_id);
                if (!$prod) continue;

                $stockAnterior = $prod->stock;
                $prod->increment('stock', $det->cantidad);

                if ($det->lote_id && $det->lote) {
                    $lote = $det->lote;
                    $lote->increment('stock_actual', $det->cantidad);
                    $lote->update(['estado' => $lote->estado_calculado]);
                }

                MovimientoInventario::create([
                    'producto_id' => $prod->id,
                    'lote_id' => $det->lote_id,
                    'user_id' => Auth::id(),
                    'tipo' => 'ENTRADA',
                    'motivo' => 'LIBERACION_PEDIDO_CANCELADO',
                    'cantidad' => $det->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockAnterior + $det->cantidad,
                    'referencia_type' => Pedido::class,
                    'referencia_id' => $pedido->id,
                ]);
            }

            $pedido->update(['estado' => 'CANCELADO']);
        });
    }

    private function siguienteNumero(): string
    {
        $ultimo = Pedido::where('numero', 'like', 'PED-%')
            ->orderByDesc('id')
            ->value('numero');

        $n = $ultimo ? ((int) substr($ultimo, 4)) + 1 : 1;

        return 'PED-' . str_pad($n, 6, '0', STR_PAD_LEFT);
    }

    private function siguienteNumeroVenta(): string
    {
        $ultimo = Venta::where('numero', 'like', 'V-%')
            ->orderByDesc('id')
            ->value('numero');

        $n = $ultimo ? ((int) substr($ultimo, 2)) + 1 : 1;

        return 'V-' . str_pad($n, 6, '0', STR_PAD_LEFT);
    }
}
