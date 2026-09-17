<?php

namespace App\Services\Inventario;

use App\Models\Producto;
use App\Models\Lote;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LoteService
{
    /**
     * Descuenta stock usando la estrategia FEFO (o un lote específico si viene provisto).
     * Retorna una colección de arreglos ['lote' => Lote, 'cantidad' => int]
     */
    public function descontarStockFEFO(Producto $producto, int $cantidadRequerida, ?int $loteId = null): Collection
    {
        if (!$producto->es_perecedero) {
            return collect();
        }

        $descuentos = collect();
        $cantidadPendiente = $cantidadRequerida;

        if ($loteId) {
            $lotes = Lote::where('id', $loteId)->where('stock_actual', '>', 0)->get();
        } else {
            $lotes = $producto->lotes()->fefoOrder()->get();
        }

        foreach ($lotes as $lote) {
            if ($cantidadPendiente <= 0) {
                break;
            }

            $cantidadADescontar = min($lote->stock_actual, $cantidadPendiente);
            $lote->stock_actual -= $cantidadADescontar;
            
            if ($lote->stock_actual <= 0) {
                $lote->estado = 'AGOTADO';
            }
            $lote->save();

            $descuentos->push([
                'lote' => $lote,
                'cantidad' => $cantidadADescontar,
            ]);

            $cantidadPendiente -= $cantidadADescontar;
        }

        return $descuentos;
    }

    /**
     * Registra o incrementa un lote en una compra o ingreso de inventario.
     */
    public function registrarLoteCompra(Producto $producto, string $codigoLote, string $fechaVencimiento, int $cantidad): Lote
    {
        $fecha = Carbon::parse($fechaVencimiento);

        $lote = Lote::where('producto_id', $producto->id)
            ->where('codigo_lote', $codigoLote)
            ->whereDate('fecha_vencimiento', $fecha)
            ->first();

        if ($lote) {
            $lote->stock_inicial += $cantidad;
            $lote->stock_actual += $cantidad;
        } else {
            $lote = new Lote([
                'empresa_id' => $producto->empresa_id,
                'producto_id' => $producto->id,
                'codigo_lote' => $codigoLote,
                'fecha_vencimiento' => $fecha,
                'stock_inicial' => $cantidad,
                'stock_actual' => $cantidad,
                'estado' => 'VIGENTE',
            ]);
        }

        $lote->estado = $lote->estado_calculado;
        $lote->save();

        return $lote;
    }

    /**
     * Actualiza el estado de todos los lotes vigentes/por vencer de la empresa.
     */
    public function actualizarEstadosLotes(): void
    {
        $lotes = Lote::where('stock_actual', '>', 0)->with('producto')->get();

        foreach ($lotes as $lote) {
            $nuevoEstado = $lote->estado_calculado;
            if ($lote->estado !== $nuevoEstado) {
                $lote->estado = $nuevoEstado;
                $lote->save();
            }
        }
    }
}
