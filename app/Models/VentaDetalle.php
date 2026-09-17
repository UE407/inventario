<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
    use BelongsToEmpresa;

    protected $table = 'venta_detalles';

    protected $fillable = [
        'empresa_id',
        'venta_id', 'producto_id', 'lote_id', 'descripcion', 'cantidad', 'precio', 'subtotal',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cantidad' => 'integer',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }
}
