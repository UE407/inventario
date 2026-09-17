<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class PedidoDetalle extends Model
{
    use BelongsToEmpresa;

    protected $table = 'pedido_detalles';

    protected $fillable = [
        'empresa_id',
        'pedido_id',
        'producto_id',
        'lote_id',
        'cantidad',
        'precio',
        'subtotal',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cantidad' => 'integer',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
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
