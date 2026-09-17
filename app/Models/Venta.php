<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use BelongsToEmpresa;

    protected $table = 'ventas';

    protected $fillable = [
        'empresa_id',
        'numero', 'cliente_id', 'user_id', 'tipo_comprobante', 'metodo_pago',
        'subtotal', 'impuesto', 'descuento', 'total', 'efectivo_recibido', 'vuelto', 'estado', 'observacion',
        // Facturación electrónica (SUNAT)
        'fe_serie', 'fe_correlativo', 'fe_estado', 'fe_hash', 'fe_ticket',
        'fe_observacion', 'fe_xml_ruta', 'fe_cdr_ruta', 'fe_enviado_en',
        // Anulación electrónica
        'fe_baja_ticket', 'fe_baja_estado', 'fe_baja_motivo',
        'fe_nc_serie', 'fe_nc_correlativo', 'fe_nc_estado', 'fe_nc_hash',
        'fe_nc_motivo', 'fe_nc_xml_ruta', 'fe_nc_cdr_ruta', 'fe_anulado_en',
        // Correo y resumen diario
        'fe_email_enviado_en', 'fe_resumen_estado', 'fe_resumen_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'efectivo_recibido' => 'decimal:2',
        'vuelto' => 'decimal:2',
        'fe_enviado_en' => 'datetime',
        'fe_anulado_en' => 'datetime',
        'fe_email_enviado_en' => 'datetime',
    ];

    /** Número del comprobante electrónico: B001-00000001 */
    public function comprobanteElectronico(): ?string
    {
        if (! $this->fe_serie || ! $this->fe_correlativo) {
            return null;
        }
        return $this->fe_serie . '-' . str_pad((string) $this->fe_correlativo, 8, '0', STR_PAD_LEFT);
    }

    /** Etiqueta legible del estado SUNAT. */
    public function feEstadoLabel(): string
    {
        return match ($this->fe_estado) {
            'ACEPTADO' => 'Aceptado por SUNAT',
            'ENVIADO' => 'Enviado a SUNAT',
            'PENDIENTE' => 'Pendiente de envío',
            'RECHAZADO' => 'Rechazado por SUNAT',
            'ERROR' => 'Error de emisión',
            default => 'No aplica',
        };
    }

    /** ¿El comprobante fue realmente emitido a SUNAT (aceptado o enviado)? */
    public function feEmitido(): bool
    {
        return in_array($this->fe_estado, ['ENVIADO', 'ACEPTADO'], true);
    }

    /** Número de la nota de crédito: BC01-00000001 */
    public function notaCreditoNumero(): ?string
    {
        if (! $this->fe_nc_serie || ! $this->fe_nc_correlativo) {
            return null;
        }
        return $this->fe_nc_serie . '-' . str_pad((string) $this->fe_nc_correlativo, 8, '0', STR_PAD_LEFT);
    }

    /** Estado de la anulación electrónica (baja o nota de crédito). */
    public function anulacionEstado(): ?string
    {
        return $this->fe_baja_estado ?? $this->fe_nc_estado;
    }

    /** Descripción del tipo de comprobante para la representación impresa. */
    public function comprobanteTitulo(): string
    {
        return match (strtoupper($this->tipo_comprobante)) {
            'FACTURA' => 'FACTURA ELECTRÓNICA',
            'BOLETA' => 'BOLETA DE VENTA ELECTRÓNICA',
            default => 'COMPROBANTE',
        };
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }
}
