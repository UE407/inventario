<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use BelongsToEmpresa;

    protected $table = 'pedidos';

    protected $fillable = [
        'empresa_id',
        'numero',
        'cliente_id',
        'ruta_id',
        'user_id',
        'fecha_pedido',
        'fecha_entrega_estimada',
        'subtotal',
        'impuesto',
        'total',
        'estado',
        'venta_id',
        'observacion',
    ];

    protected $casts = [
        'fecha_pedido' => 'datetime',
        'fecha_entrega_estimada' => 'date',
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class);
    }

    /**
     * Scope para filtrar según los permisos del vendedor en ruta.
     */
    public function scopeVisiblesParaUsuario($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        if ($user && $user->rol === 'vendedor') {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }
}
