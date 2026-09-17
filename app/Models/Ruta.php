<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use BelongsToEmpresa;

    protected $table = 'rutas';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'codigo',
        'dias_entrega_estimados',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'dias_entrega_estimados' => 'integer',
        'activo' => 'boolean',
    ];

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}
