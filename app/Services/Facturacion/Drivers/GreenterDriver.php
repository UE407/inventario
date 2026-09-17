<?php

namespace App\Services\Facturacion\Drivers;

use App\Models\Empresa;
use App\Models\FacturacionConfig;
use App\Models\Venta;
use App\Services\Facturacion\Contracts\FacturadorDriver;
use App\Services\Facturacion\ResultadoEmision;
use Illuminate\Support\Facades\Storage;

/**
 * Driver de emisión electrónica ante SUNAT usando la librería Greenter
 * (UBL 2.1). Genera el XML, lo firma con el certificado digital y lo envía
 * al servicio de SUNAT (Beta u Producción).
 *
 * REQUISITO: instalar la librería en el proyecto:
 *     composer require greenter/greenter
 *
 * Y contar con un certificado digital .pem válido (en Beta se puede usar el
 * certificado de homologación de SUNAT). Mientras Greenter no esté instalado,
 * este driver informa el paso pendiente sin romper el sistema.
 */
class GreenterDriver implements FacturadorDriver
{
    public function nombre(): string
    {
        return 'greenter';
    }

    // ------------------------------------------------------------------

    public function probarConexion(FacturacionConfig $config): ResultadoEmision
    {
        if (! $this->greenterInstalado()) {
            return ResultadoEmision::error($this->mensajeInstalacion());
        }

        if (empty($config->ruc) || empty($config->sol_user) || empty($config->sol_pass)) {
            return ResultadoEmision::error('Faltan credenciales SUNAT (RUC, usuario o clave SOL).');
        }

        if (! $config->certificadoExiste()) {
            return ResultadoEmision::error('No se encontró el certificado (.pem) en la ruta indicada.');
        }

        try {
            $this->construirSee($config); // valida certificado y credenciales
        } catch (\Throwable $e) {
            return ResultadoEmision::error('No se pudo inicializar la conexión: ' . $e->getMessage());
        }

        $entorno = $config->entorno === 'produccion' ? 'Producción' : 'Beta (homologación)';

        return ResultadoEmision::ok(
            'ENVIADO',
            "Certificado y credenciales cargados correctamente. Entorno: {$entorno}. "
            . 'Emite una boleta/factura de prueba para validar la respuesta (CDR) de SUNAT.'
        );
    }

    // ------------------------------------------------------------------

    public function emitir(Venta $venta, FacturacionConfig $config): ResultadoEmision
    {
        if (! $this->greenterInstalado()) {
            // No rompemos la venta: queda pendiente hasta instalar la librería.
            return ResultadoEmision::error(
                $this->mensajeInstalacion() . ' El comprobante quedó PENDIENTE.',
                'PENDIENTE'
            );
        }

        if (! $config->certificadoExiste()) {
            return ResultadoEmision::error(
                'No se encontró el certificado digital. El comprobante quedó PENDIENTE.',
                'PENDIENTE'
            );
        }

        $see = $this->construirSee($config);
        $invoice = $this->construirComprobante($venta, $config);

        // Firma + envío a SUNAT.
        $result = $see->send($invoice);

        // Guarda el XML firmado.
        $xmlRuta = null;
        try {
            $xmlFirmado = $see->getFactory()->getLastXml();
            if ($xmlFirmado) {
                $nombre = $invoice->getName(); // p.ej. 20000000001-03-B001-1
                $xmlRuta = "facturacion/xml/{$nombre}.xml";
                Storage::disk('local')->put($xmlRuta, $xmlFirmado);
            }
        } catch (\Throwable $e) {
            // El XML es informativo; no bloquea el resultado.
        }

        // El hash (DigestValue) puede extraerse del XML firmado si se requiere.
        $hash = $this->extraerHash($see);

        if (! $result->isSuccess()) {
            $error = $result->getError();
            return ResultadoEmision::error(
                'SUNAT rechazó el envío: ' . ($error ? $error->getCode() . ' - ' . $error->getMessage() : 'desconocido'),
                'RECHAZADO',
                ['xmlRuta' => $xmlRuta]
            );
        }

        // Procesa el CDR (Constancia de Recepción).
        $cdrRuta = null;
        $cdr = $result->getCdrResponse();
        try {
            $zip = $result->getCdrZip();
            if ($zip) {
                $nombre = $invoice->getName();
                $cdrRuta = "facturacion/cdr/R-{$nombre}.zip";
                Storage::disk('local')->put($cdrRuta, $zip);
            }
        } catch (\Throwable $e) {
            // opcional
        }

        $code = $cdr ? $cdr->getCode() : null;

        if ($code === '0' || $code === 0) {
            return ResultadoEmision::ok(
                'ACEPTADO',
                'Comprobante aceptado por SUNAT.' . ($cdr ? ' ' . $cdr->getDescription() : ''),
                ['cdrRuta' => $cdrRuta, 'xmlRuta' => $xmlRuta, 'hash' => $hash]
            );
        }

        return ResultadoEmision::ok(
            'ENVIADO',
            'Comprobante enviado a SUNAT.' . ($cdr ? ' ' . $cdr->getDescription() : ''),
            ['cdrRuta' => $cdrRuta, 'xmlRuta' => $xmlRuta, 'hash' => $hash]
        );
    }

    /** Extrae el DigestValue (hash) del último XML firmado, si es posible. */
    protected function extraerHash($see): ?string
    {
        try {
            $xml = $see->getFactory()->getLastXml();
            if (! $xml) {
                return null;
            }
            $doc = new \DOMDocument();
            $doc->loadXML($xml);
            $nodos = $doc->getElementsByTagName('DigestValue');
            return $nodos->length ? $nodos->item(0)->nodeValue : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ------------------------------------------------------------------
    // Construcción de objetos Greenter
    // ------------------------------------------------------------------

    /** Crea y configura el objeto See (firmante + endpoint SUNAT). */
    protected function construirSee(FacturacionConfig $config)
    {
        $seeClass = '\\Greenter\\See';
        $endpointsClass = '\\Greenter\\Ws\\Services\\SunatEndpoints';

        /** @var object $see */
        $see = new $seeClass();
        $see->setCertificate(file_get_contents($config->certificado_ruta));
        $see->setService(
            $config->entorno === 'produccion'
                ? $endpointsClass::FE_PRODUCCION
                : $endpointsClass::FE_BETA
        );
        $see->setClaveSOL($config->ruc, $config->sol_user, $config->sol_pass);

        return $see;
    }

    /** Arma el comprobante (Invoice) a partir de la venta. */
    protected function construirComprobante(Venta $venta, FacturacionConfig $config)
    {
        $empresa = Empresa::actual();
        $tasaIgv = $empresa->tasaIgv(); // p.ej. 0.18
        $esFactura = strtoupper($venta->tipo_comprobante) === 'FACTURA';

        $address = (new \Greenter\Model\Company\Address())
            ->setUbigueo($config->ubigeo ?: '150101')
            ->setDepartamento($config->departamento ?: 'LIMA')
            ->setProvincia($config->provincia ?: 'LIMA')
            ->setDistrito($config->distrito ?: 'LIMA')
            ->setDireccion($config->direccion_fiscal ?: '-');

        $company = (new \Greenter\Model\Company\Company())
            ->setRuc($config->ruc)
            ->setRazonSocial($config->razon_social)
            ->setNombreComercial($config->nombre_comercial ?: $config->razon_social)
            ->setAddress($address);

        // Cliente
        $cliente = $venta->cliente;
        if ($esFactura) {
            $tipoDocCliente = '6'; // RUC
            $numDocCliente = $cliente->numero_documento ?? '00000000000';
        } else {
            $tipoDocCliente = '1'; // DNI
            $numDocCliente = $cliente->numero_documento ?? '00000000';
        }
        $client = (new \Greenter\Model\Client\Client())
            ->setTipoDoc($tipoDocCliente)
            ->setNumDoc($numDocCliente)
            ->setRznSocial($cliente->nombre ?? 'CLIENTE VARIOS');

        // Detalle (con prorrateo del descuento global para cuadrar con la venta).
        $r = $this->construirDetalles($venta, $tasaIgv);
        $detalles = $r['detalles'];
        $gravadas = $r['gravadas'];
        $igvTotal = $r['igv'];
        $total = $r['total'];

        $legend = (new \Greenter\Model\Sale\Legend())
            ->setCode('1000')
            ->setValue(\App\Support\NumeroALetras::moneda($total, $empresa->moneda));

        $tipoDoc = $esFactura ? '01' : '03';

        $invoice = (new \Greenter\Model\Sale\Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101') // Venta interna
            ->setTipoDoc($tipoDoc)
            ->setSerie($venta->fe_serie)
            ->setCorrelativo((string) $venta->fe_correlativo)
            ->setFechaEmision(new \DateTime())
            ->setTipoMoneda('PEN')
            ->setCompany($company)
            ->setClient($client)
            ->setMtoOperGravadas($gravadas)
            ->setMtoIGV($igvTotal)
            ->setTotalImpuestos($igvTotal)
            ->setValorVenta($gravadas)
            ->setSubTotal($total)
            ->setMtoImpVenta($total)
            ->setDetails($detalles)
            ->setLegends([$legend]);

        return $invoice;
    }

    // ------------------------------------------------------------------

    protected function greenterInstalado(): bool
    {
        return class_exists('\\Greenter\\See');
    }

    protected function mensajeInstalacion(): string
    {
        return 'La librería Greenter no está instalada. Ejecuta "composer require greenter/greenter" en el servidor para habilitar la emisión ante SUNAT.';
    }

    // ==================================================================
    // Anulación electrónica
    // ==================================================================

    /**
     * Anula un comprobante ya aceptado:
     *  - FACTURA -> Comunicación de Baja (RA). Devuelve ticket de SUNAT.
     *  - BOLETA  -> Nota de Crédito (07) con motivo "01" (anulación).
     */
    public function anular(Venta $venta, string $motivo, FacturacionConfig $config): ResultadoEmision
    {
        if (! $this->greenterInstalado()) {
            return ResultadoEmision::error($this->mensajeInstalacion(), 'PENDIENTE');
        }
        if (! $config->certificadoExiste()) {
            return ResultadoEmision::error('No se encontró el certificado digital.', 'PENDIENTE');
        }

        return strtoupper($venta->tipo_comprobante) === 'FACTURA'
            ? $this->comunicacionBaja($venta, $motivo, $config)
            : $this->notaCredito($venta, $motivo, $config);
    }

    /** Comunicación de baja de una factura (documento RA). */
    protected function comunicacionBaja(Venta $venta, string $motivo, FacturacionConfig $config): ResultadoEmision
    {
        $see = $this->construirSee($config);

        $baja = (new \Greenter\Model\Voided\Voided())
            ->setCorrelativo('1')
            ->setFecComunicacion(new \DateTime())
            ->setFecGeneracion($venta->created_at ? $venta->created_at->toDate() : new \DateTime())
            ->setCompany($this->company($config))
            ->setDetails([
                (new \Greenter\Model\Voided\VoidedDetail())
                    ->setTipoDoc('01')
                    ->setSerie($venta->fe_serie)
                    ->setCorrelativo((string) $venta->fe_correlativo)
                    ->setDesMotivoBaja($motivo ?: 'ANULACION DE LA OPERACION'),
            ]);

        $result = $see->send($baja);

        if (! $result->isSuccess()) {
            $e = $result->getError();
            return ResultadoEmision::error(
                'SUNAT rechazó la baja: ' . ($e ? $e->getCode() . ' - ' . $e->getMessage() : 'desconocido'),
                'ERROR'
            );
        }

        return ResultadoEmision::ok(
            'ENVIADO',
            'Comunicación de baja enviada a SUNAT.',
            ['ticket' => $result->getTicket()]
        );
    }

    /** Nota de crédito (tipo 07) por anulación de la operación. */
    protected function notaCredito(Venta $venta, string $motivo, FacturacionConfig $config): ResultadoEmision
    {
        $empresa = Empresa::actual();
        $tasaIgv = $empresa->tasaIgv();
        $serie = $config->serieNotaCreditoDe($venta->tipo_comprobante);
        $correlativo = $this->siguienteCorrelativoNc($config, $serie);

        // Detalle: se replican las líneas del comprobante original (con su descuento).
        $r = $this->construirDetalles($venta, $tasaIgv);
        $detalles = $r['detalles'];
        $gravadas = $r['gravadas'];
        $igvTotal = $r['igv'];
        $total = $r['total'];

        $legend = (new \Greenter\Model\Sale\Legend())
            ->setCode('1000')
            ->setValue(\App\Support\NumeroALetras::moneda($total, $empresa->moneda));

        $client = $this->client($venta, strtoupper($venta->tipo_comprobante) === 'FACTURA');

        $note = (new \Greenter\Model\Sale\Note())
            ->setUblVersion('2.1')
            ->setTipoDoc('07')                     // Nota de crédito
            ->setSerie($serie)
            ->setCorrelativo((string) $correlativo)
            ->setFechaEmision(new \DateTime())
            ->setTipDocAfectado(strtoupper($venta->tipo_comprobante) === 'FACTURA' ? '01' : '03')
            ->setNumDocfectado($venta->fe_serie . '-' . $venta->fe_correlativo)
            ->setCodMotivo('01')                   // Anulación de la operación
            ->setDesMotivo($motivo ?: 'ANULACION DE LA OPERACION')
            ->setTipoMoneda('PEN')
            ->setCompany($this->company($config))
            ->setClient($client)
            ->setMtoOperGravadas($gravadas)
            ->setMtoIGV($igvTotal)
            ->setTotalImpuestos($igvTotal)
            ->setMtoImpVenta($total)
            ->setDetails($detalles)
            ->setLegends([$legend]);

        $see = $this->construirSee($config);
        $result = $see->send($note);

        $xmlRuta = null;
        try {
            $xml = $see->getFactory()->getLastXml();
            if ($xml) {
                $xmlRuta = "facturacion/xml/{$note->getName()}.xml";
                Storage::disk('local')->put($xmlRuta, $xml);
            }
        } catch (\Throwable $e) {
        }

        if (! $result->isSuccess()) {
            $e = $result->getError();
            return ResultadoEmision::error(
                'SUNAT rechazó la nota de crédito: ' . ($e ? $e->getCode() . ' - ' . $e->getMessage() : 'desconocido'),
                'ERROR',
                ['serie' => $serie, 'correlativo' => $correlativo, 'xmlRuta' => $xmlRuta]
            );
        }

        $cdrRuta = null;
        try {
            $zip = $result->getCdrZip();
            if ($zip) {
                $cdrRuta = "facturacion/cdr/R-{$note->getName()}.zip";
                Storage::disk('local')->put($cdrRuta, $zip);
            }
        } catch (\Throwable $e) {
        }

        $cdr = $result->getCdrResponse();
        $code = $cdr ? $cdr->getCode() : null;
        $estado = ($code === '0' || $code === 0) ? 'ACEPTADO' : 'ENVIADO';

        return ResultadoEmision::ok(
            $estado,
            'Nota de crédito ' . ($estado === 'ACEPTADO' ? 'aceptada' : 'enviada') . '.'
                . ($cdr ? ' ' . $cdr->getDescription() : ''),
            ['serie' => $serie, 'correlativo' => $correlativo, 'xmlRuta' => $xmlRuta, 'cdrRuta' => $cdrRuta, 'hash' => $this->extraerHash($see)]
        );
    }

    /**
     * Construye las líneas del comprobante prorrateando el descuento global de
     * la venta para que el total del XML coincida exactamente con la venta
     * registrada (base = subtotal - descuento; IGV = venta->impuesto).
     *
     * @return array{detalles: array, gravadas: float, igv: float, total: float}
     */
    protected function construirDetalles(Venta $venta, float $tasaIgv): array
    {
        // Valor de venta bruto (sin descuento) por línea.
        $lineas = [];
        $brutoTotal = 0.0;
        foreach ($venta->detalles as $d) {
            $cant = (float) $d->cantidad;
            $bruto = round((float) $d->precio * $cant, 2);
            $brutoTotal += $bruto;
            $lineas[] = ['d' => $d, 'cant' => $cant ?: 1, 'bruto' => $bruto];
        }
        $brutoTotal = round($brutoTotal, 2);

        $descuento = round((float) $venta->descuento, 2);
        $baseObjetivo = round($brutoTotal - $descuento, 2);
        $igvObjetivo = round((float) $venta->impuesto, 2);
        $factor = ($descuento > 0 && $brutoTotal > 0) ? ($baseObjetivo / $brutoTotal) : 1.0;

        $detalles = [];
        $sumBase = 0.0;
        $sumIgv = 0.0;
        $n = count($lineas);

        foreach ($lineas as $i => $l) {
            $valorVenta = round($l['bruto'] * $factor, 2);
            $igv = round($valorVenta * $tasaIgv, 2);

            // La última línea absorbe el residuo para cuadrar con la venta.
            if ($i === $n - 1) {
                $valorVenta = round($baseObjetivo - $sumBase, 2);
                $igv = round($igvObjetivo - $sumIgv, 2);
            }

            $sumBase = round($sumBase + $valorVenta, 2);
            $sumIgv = round($sumIgv + $igv, 2);

            $cant = $l['cant'];
            $valorUnit = round($valorVenta / $cant, 6);
            $precioUnit = round(($valorVenta + $igv) / $cant, 6);

            $detalles[] = (new \Greenter\Model\Sale\SaleDetail())
                ->setCodProducto((string) $l['d']->producto_id)
                ->setUnidad('NIU')
                ->setCantidad($cant)
                ->setDescripcion($l['d']->descripcion)
                ->setMtoBaseIgv($valorVenta)
                ->setPorcentajeIgv($tasaIgv * 100)
                ->setIgv($igv)
                ->setTipAfeIgv('10') // Gravado - Operación Onerosa
                ->setTotalImpuestos($igv)
                ->setMtoValorVenta($valorVenta)
                ->setMtoValorUnitario($valorUnit)
                ->setMtoPrecioUnitario($precioUnit);
        }

        $gravadas = round($sumBase, 2);
        $igvTotal = round($sumIgv, 2);

        return [
            'detalles' => $detalles,
            'gravadas' => $gravadas,
            'igv' => $igvTotal,
            'total' => round($gravadas + $igvTotal, 2),
        ];
    }

    /** Company reutilizable para baja / nota de crédito. */
    protected function company(FacturacionConfig $config)
    {
        $address = (new \Greenter\Model\Company\Address())
            ->setUbigueo($config->ubigeo ?: '150101')
            ->setDepartamento($config->departamento ?: 'LIMA')
            ->setProvincia($config->provincia ?: 'LIMA')
            ->setDistrito($config->distrito ?: 'LIMA')
            ->setDireccion($config->direccion_fiscal ?: '-');

        return (new \Greenter\Model\Company\Company())
            ->setRuc($config->ruc)
            ->setRazonSocial($config->razon_social)
            ->setNombreComercial($config->nombre_comercial ?: $config->razon_social)
            ->setAddress($address);
    }

    /** Client reutilizable. */
    protected function client(Venta $venta, bool $esFactura)
    {
        $cliente = $venta->cliente;
        $tipoDoc = $esFactura ? '6' : '1';
        $numDoc = $cliente->numero_documento ?? ($esFactura ? '00000000000' : '00000000');

        return (new \Greenter\Model\Client\Client())
            ->setTipoDoc($tipoDoc)
            ->setNumDoc($numDoc)
            ->setRznSocial($cliente->nombre ?? 'CLIENTE VARIOS');
    }

    protected function siguienteCorrelativoNc(FacturacionConfig $config, string $serie): int
    {
        $ultimo = Venta::where('empresa_id', $config->empresa_id)
            ->where('fe_nc_serie', $serie)
            ->max('fe_nc_correlativo');

        return ((int) $ultimo) + 1;
    }

    // ==================================================================
    // Resumen diario de boletas (RC)
    // ==================================================================

    /**
     * Envía a SUNAT el resumen diario de boletas. Devuelve el ticket para
     * consultar luego el resultado (SUNAT procesa de forma asíncrona).
     *
     * @param  iterable  $boletas  Ventas tipo BOLETA a resumir.
     */
    public function enviarResumenBoletas(
        iterable $boletas,
        \DateTimeInterface $fechaReferencia,
        \DateTimeInterface $fechaGeneracion,
        string $identificador,
        FacturacionConfig $config
    ): ResultadoEmision {
        if (! $this->greenterInstalado()) {
            return ResultadoEmision::error($this->mensajeInstalacion(), 'PENDIENTE');
        }
        if (! $config->certificadoExiste()) {
            return ResultadoEmision::error('No se encontró el certificado digital.', 'PENDIENTE');
        }

        $empresa = Empresa::actual();
        $tasaIgv = $empresa->tasaIgv();

        $detalles = [];
        foreach ($boletas as $venta) {
            $gravadas = round((float) $venta->subtotal - (float) $venta->descuento, 2);
            $igv = round((float) $venta->impuesto, 2);
            $total = round((float) $venta->total, 2);

            // Estado: 1 = Adicionar, 3 = Anular (boleta ya informada y anulada).
            $estado = $venta->estado === 'ANULADA' ? '3' : '1';

            // Documento real del cliente (RUC 11 dígitos -> tipo 6; si no, DNI).
            $doc = $venta->cliente->numero_documento ?? '';
            $tipoDocCli = strlen($doc) === 11 ? '6' : '1';
            $numDocCli = $doc !== '' ? $doc : '00000000';

            $detalles[] = (new \Greenter\Model\Summary\SummaryDetail())
                ->setTipoDoc('03')
                ->setSerieNro($venta->fe_serie . '-' . $venta->fe_correlativo)
                ->setEstado($estado)
                ->setClienteTipo($tipoDocCli)
                ->setClienteNro($numDocCli)
                ->setTotal($total)
                ->setMtoOperGravadas($gravadas)
                ->setMtoIGV($igv);
        }

        $correlativo = ltrim((string) substr(strrchr($identificador, '-') ?: '-1', 1), '0') ?: '1';

        $summary = (new \Greenter\Model\Summary\Summary())
            ->setFecGeneracion($fechaReferencia)
            ->setFecResumen($fechaGeneracion)
            ->setCorrelativo($correlativo)
            ->setCompany($this->company($config))
            ->setDetails($detalles);

        // Greenter arma internamente el ID (RC-YYYYMMDD-correlativo).
        $see = $this->construirSee($config);
        $result = $see->send($summary);

        $xmlRuta = null;
        try {
            $xml = $see->getFactory()->getLastXml();
            if ($xml) {
                $xmlRuta = "facturacion/resumen/{$summary->getName()}.xml";
                Storage::disk('local')->put($xmlRuta, $xml);
            }
        } catch (\Throwable $e) {
        }

        if (! $result->isSuccess()) {
            $e = $result->getError();
            return ResultadoEmision::error(
                'SUNAT rechazó el resumen: ' . ($e ? $e->getCode() . ' - ' . $e->getMessage() : 'desconocido'),
                'ERROR',
                ['xmlRuta' => $xmlRuta]
            );
        }

        return ResultadoEmision::ok(
            'ENVIADO',
            'Resumen enviado. Ticket ' . $result->getTicket() . '. Consulte el resultado en unos minutos.',
            ['ticket' => $result->getTicket(), 'xmlRuta' => $xmlRuta]
        );
    }

    /** Consulta a SUNAT el estado de un resumen previamente enviado (por ticket). */
    public function consultarTicket(string $ticket, FacturacionConfig $config): ResultadoEmision
    {
        if (! $this->greenterInstalado()) {
            return ResultadoEmision::error($this->mensajeInstalacion(), 'ENVIADO');
        }

        $see = $this->construirSee($config);
        $status = $see->getStatus($ticket);

        if (! $status->isSuccess()) {
            $e = $status->getError();
            return ResultadoEmision::error(
                'Resumen aún en proceso o con error: ' . ($e ? $e->getCode() . ' - ' . $e->getMessage() : ''),
                'ENVIADO'
            );
        }

        $cdrRuta = null;
        try {
            $zip = $status->getCdrZip();
            if ($zip) {
                $cdrRuta = "facturacion/resumen/cdr/R-{$ticket}.zip";
                Storage::disk('local')->put($cdrRuta, $zip);
            }
        } catch (\Throwable $e) {
        }

        $cdr = $status->getCdrResponse();
        $code = $cdr ? $cdr->getCode() : null;
        $estado = ($code === '0' || $code === 0) ? 'ACEPTADO' : 'RECHAZADO';

        return ResultadoEmision::ok(
            $estado,
            'Resumen ' . ($estado === 'ACEPTADO' ? 'aceptado' : 'rechazado') . ' por SUNAT.'
                . ($cdr ? ' ' . $cdr->getDescription() : ''),
            ['cdrRuta' => $cdrRuta]
        );
    }
}
