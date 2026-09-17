<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use BelongsToEmpresa;

    protected $table = 'productos';

    protected $fillable = [
        'empresa_id',
        'codigo', 'nombre', 'descripcion', 'categoria_id', 'marca_id',
        'unidad', 'precio_compra', 'precio_venta', 'stock', 'stock_minimo',
        'imagen', 'activo', 'es_perecedero', 'dias_alerta_vencimiento',
        'tiene_empaque', 'nombre_empaque', 'cant_por_empaque', 'precio_venta_empaque',
        'tiene_variantes',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'precio_venta_empaque' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'cant_por_empaque' => 'integer',
        'activo' => 'boolean',
        'es_perecedero' => 'boolean',
        'tiene_empaque' => 'boolean',
        'tiene_variantes' => 'boolean',
        'dias_alerta_vencimiento' => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    public function loteSiguienteFEFO()
    {
        return $this->lotes()->fefoOrder()->first();
    }
}
