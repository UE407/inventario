<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class ProductoVariante extends Model
{
    use BelongsToEmpresa;

    protected $table = 'producto_variantes';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'codigo',
        'sabor',
        'stock',
        'stock_minimo',
        'activo',
    ];

    protected $casts = [
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'activo' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
