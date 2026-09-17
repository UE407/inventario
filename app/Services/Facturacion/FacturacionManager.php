<?php

namespace App\Services\Facturacion;

use App\Models\FacturacionConfig;
use App\Models\FacturacionResumen;
use App\Models\Venta;
use App\Services\Facturacion\Contracts\FacturadorDriver;
use App\Services\Facturacion\Drivers\GreenterDriver;
use App\Services\Facturacion\Drivers\NullDriver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Punto de entrada de la Facturación Electrónica.
 * Resuelve el driver adecuado según la configuración de la empresa y
 * coordina la asignación de serie/correlativo y la actualización de la venta.
 */
class FacturacionManager
{
    /** Resuelve la instancia del driver a partir de su nombre. */
    public function driver(string $nombre): FacturadorDriver
    {
        return match ($nombre) {
            'greenter' => new GreenterDriver(),
            'fel_guatemala' => new \App\Services\Facturacion\Drivers\FelGuatemalaDriver(),
            default => new NullDriver(),
        };
    }

    /** Prueba la conexión con SUNAT usando la config indicada. */
    public function probarConexion(FacturacionConfig $config): ResultadoEmision
    {
        return $this->driver($config->driver)->probarConexion($config);
    }

    /**
     * Procesa la emisión electrónica de una venta recién registrada.
     * Nunca lanza excepción: cualquier fallo deja la venta en estado ERROR
     * o PENDIENTE para no romper el cierre de la venta en el POS.
     */
    public function emitirParaVenta(Venta $venta, bool $forzar = false): ResultadoEmision
    {
        $config = FacturacionConfig::actual();
        $tipo = strtoupper($venta->tipo_comprobante);

        // Tickets internos nunca van a SUNAT.
        if (! in_array($tipo, ['BOLETA', 'FACTURA'], true)) {
            return $this->aplicar($venta, ResultadoEmision::ok('NO_APLICA', 'Ticket interno.'));
        }

        // FE deshabilitada: se guarda como comprobante interno sin emisión.
        if (! $config->habilitado) {
            return $this->aplicar($venta, ResultadoEmision::ok('NO_APLICA', 'Facturación electrónica deshabilitada.'));
        }

        // Asigna serie y correlativo (idempotente: conserva los existentes en reintentos).
        $serie = $config->serieDe($tipo);
        if ($serie) {
            $venta->fe_serie = $venta->fe_serie ?: $serie;
            if (empty($venta->fe_correlativo)) {
                $venta->fe_correlativo = $this->siguienteCorrelativo($config, $venta->fe_serie);
            }
        }

        // Boletas en modo "resumen": no se envían individualmente; se numeran y
        // quedan PENDIENTE para declararse por el Resumen Diario (RC).
        if ($tipo === 'BOLETA' && $config->boletaPorResumen()) {
            return $this->aplicar($venta, ResultadoEmision::ok(
                'PENDIENTE',
                'Boleta numerada. Se declarará por el Resumen Diario de boletas (RC).'
            ));
        }

        // Sin emisión automática (y sin forzar): se numera y queda pendiente.
        if (! $config->emitir_automatico && ! $forzar) {
            return $this->aplicar($venta, ResultadoEmision::ok(
                'PENDIENTE',
                'Comprobante numerado. Emisión automática desactivada: pendiente de envío a SUNAT.'
            ));
        }

        try {
            $resultado = $this->driver($config->driver)->emitir($venta, $config);
        } catch (\Throwable $e) {
            Log::error('Fallo al emitir comprobante electrónico', [
                'venta_id' => $venta->id,
                'error' => $e->getMessage(),
            ]);
            $resultado = ResultadoEmision::error('Error interno al emitir: ' . $e->getMessage());
        }

        return $this->aplicar($venta, $resultado);
    }

    /**
     * Reenvía a SUNAT todos los comprobantes de la empresa activa que
     * quedaron en PENDIENTE o ERROR. Devuelve un resumen del proceso.
     */
    public function reintentarPendientes(): array
    {
        $config = FacturacionConfig::actual();

        $ventas = Venta::whereIn('fe_estado', ['PENDIENTE', 'ERROR'])
            ->where('estado', 'COMPLETADA')
            // En modo "resumen" las boletas no se envían individualmente.
            ->when($config->boletaPorResumen(), fn ($q) => $q->where('tipo_comprobante', '!=', 'BOLETA'))
            ->get();

        $ok = 0;
        $fail = 0;
        foreach ($ventas as $venta) {
            $r = $this->emitirParaVenta($venta, true);
            if ($r->exito && in_array($r->estado, ['ENVIADO', 'ACEPTADO'], true)) {
                $ok++;
            } else {
                $fail++;
            }
        }

        return ['total' => $ventas->count(), 'ok' => $ok, 'fail' => $fail];
    }

    /**
     * Genera y envía a SUNAT el resumen diario de boletas de una fecha
     * (por defecto, el día anterior). Marca las boletas incluidas.
     */
    public function enviarResumenDiario(?Carbon $fecha = null): array
    {
        $config = FacturacionConfig::actual();

        if (! $config->activa()) {
            return ['ok' => false, 'mensaje' => 'Facturación electrónica deshabilitada o sin driver.', 'cantidad' => 0];
        }
        if ($config->driver !== 'greenter') {
            return ['ok' => false, 'mensaje' => 'El resumen de boletas requiere el driver Greenter.', 'cantidad' => 0];
        }

        $fecha = $fecha ? $fecha->copy()->startOfDay() : Carbon::yesterday();

        // Boletas numeradas (PENDIENTE), completadas, aún no incluidas en un RC.
        $boletas = Venta::where('tipo_comprobante', 'BOLETA')
            ->where('fe_estado', 'PENDIENTE')
            ->where('estado', 'COMPLETADA')
            ->whereNull('fe_resumen_id')
            ->whereNotNull('fe_correlativo')
            ->whereDate('created_at', $fecha->toDateString())
            ->with('cliente')
            ->get();

        if ($boletas->isEmpty()) {
            return ['ok' => true, 'mensaje' => "No hay boletas por resumir del {$fecha->format('d/m/Y')}.", 'cantidad' => 0];
        }

        // Correlativo del día (los intentos con ERROR no consumen numeración).
        $correlativo = FacturacionResumen::whereDate('fecha_referencia', $fecha->toDateString())
            ->where('estado', '!=', 'ERROR')
            ->count() + 1;
        $identificador = 'RC-' . $fecha->format('Ymd') . '-' . $correlativo;

        $resumen = FacturacionResumen::create([
            'fecha_referencia' => $fecha->toDateString(),
            'fecha_generacion' => now()->toDateString(),
            'identificador' => $identificador,
            'cantidad' => $boletas->count(),
            'estado' => 'PENDIENTE',
        ]);

        $driver = new GreenterDriver();

        try {
            $r = $driver->enviarResumenBoletas($boletas, $fecha, now(), $identificador, $config);
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar resumen de boletas', ['error' => $e->getMessage()]);
            $r = ResultadoEmision::error('Error interno al enviar el resumen: ' . $e->getMessage());
        }

        $resumen->fill([
            'estado' => $r->estado,
            'ticket' => $r->ticket,
            'mensaje' => $r->mensaje,
            'xml_ruta' => $r->xmlRuta,
        ])->save();

        if ($r->exito) {
            foreach ($boletas as $b) {
                $b->forceFill(['fe_resumen_id' => $resumen->id, 'fe_resumen_estado' => $r->estado])->save();
            }

            // Intento de consulta inmediata del ticket (SUNAT procesa asíncrono).
            if ($r->ticket) {
                try {
                    $c = $driver->consultarTicket($r->ticket, $config);
                    if ($c->exito) {
                        $resumen->fill(['estado' => $c->estado, 'cdr_ruta' => $c->cdrRuta, 'mensaje' => $c->mensaje])->save();
                        foreach ($boletas as $b) {
                            // Al aceptar el RC, la boleta queda formalmente declarada.
                            $nuevoEstado = $c->estado === 'ACEPTADO' ? 'ACEPTADO' : $b->fe_estado;
                            $b->forceFill(['fe_estado' => $nuevoEstado, 'fe_resumen_estado' => $c->estado])->save();
                        }
                    }
                } catch (\Throwable $e) {
                    // El resultado quedará ENVIADO; se puede consultar más tarde.
                }
            }
        }

        return [
            'ok' => $r->exito,
            'mensaje' => $r->mensaje,
            'cantidad' => $boletas->count(),
            'estado' => $resumen->fresh()->estado,
        ];
    }

    /**
     * Anula electrónicamente el comprobante de una venta ya emitida.
     * No lanza excepción: cualquier fallo deja constancia en la venta.
     */
    public function anularVenta(Venta $venta, string $motivo = ''): ResultadoEmision
    {
        $config = FacturacionConfig::actual();
        $tipo = strtoupper($venta->tipo_comprobante);
        $esFactura = $tipo === 'FACTURA';

        // Solo tiene sentido anular comprobantes que llegaron a SUNAT.
        if (! in_array($venta->fe_estado, ['ENVIADO', 'ACEPTADO'], true)) {
            return new ResultadoEmision(true, $venta->fe_estado ?? 'NO_APLICA',
                'El comprobante no fue aceptado por SUNAT; no requiere anulación electrónica.');
        }

        try {
            $r = $this->driver($config->driver)->anular($venta, $motivo, $config);
        } catch (\Throwable $e) {
            Log::error('Fallo al anular comprobante electrónico', [
                'venta_id' => $venta->id,
                'error' => $e->getMessage(),
            ]);
            $r = ResultadoEmision::error('Error interno al anular: ' . $e->getMessage());
        }

        return $this->aplicarAnulacion($venta, $r, $esFactura, $motivo);
    }

    /** Persiste el resultado de la anulación en los campos de baja o NC. */
    protected function aplicarAnulacion(Venta $venta, ResultadoEmision $r, bool $esFactura, string $motivo): ResultadoEmision
    {
        if ($esFactura) {
            $venta->fe_baja_estado = $r->estado;
            $venta->fe_baja_motivo = $motivo ?: 'ANULACION DE LA OPERACION';
            if ($r->ticket) {
                $venta->fe_baja_ticket = $r->ticket;
            }
        } else {
            $venta->fe_nc_estado = $r->estado;
            $venta->fe_nc_motivo = $motivo ?: 'ANULACION DE LA OPERACION';
            if ($r->serie) {
                $venta->fe_nc_serie = $r->serie;
            }
            if ($r->correlativo) {
                $venta->fe_nc_correlativo = $r->correlativo;
            }
            if ($r->hash) {
                $venta->fe_nc_hash = $r->hash;
            }
            if ($r->xmlRuta) {
                $venta->fe_nc_xml_ruta = $r->xmlRuta;
            }
            if ($r->cdrRuta) {
                $venta->fe_nc_cdr_ruta = $r->cdrRuta;
            }
        }

        if (in_array($r->estado, ['ENVIADO', 'ACEPTADO'], true)) {
            $venta->fe_anulado_en = now();
        }
        $venta->save();

        return $r;
    }

    /** Vuelca el resultado sobre la venta y lo persiste. */
    protected function aplicar(Venta $venta, ResultadoEmision $r): ResultadoEmision
    {
        $venta->fe_estado = $r->estado;
        $venta->fe_observacion = $r->mensaje ?: $venta->fe_observacion;
        if ($r->hash) {
            $venta->fe_hash = $r->hash;
        }
        if ($r->ticket) {
            $venta->fe_ticket = $r->ticket;
        }
        if ($r->xmlRuta) {
            $venta->fe_xml_ruta = $r->xmlRuta;
        }
        if ($r->cdrRuta) {
            $venta->fe_cdr_ruta = $r->cdrRuta;
        }
        if ($r->serie) {
            $venta->fe_serie = $r->serie;
        }
        if ($r->correlativo) {
            $venta->fe_correlativo = $r->correlativo;
        }
        if (in_array($r->estado, ['ENVIADO', 'ACEPTADO', 'RECHAZADO'], true)) {
            $venta->fe_enviado_en = now();
        }
        $venta->save();

        return $r;
    }

    /** Siguiente correlativo disponible para una serie dentro de la empresa. */
    protected function siguienteCorrelativo(FacturacionConfig $config, string $serie): int
    {
        $ultimo = Venta::where('empresa_id', $config->empresa_id)
            ->where('fe_serie', $serie)
            ->max('fe_correlativo');

        return ((int) $ultimo) + 1;
    }
}
