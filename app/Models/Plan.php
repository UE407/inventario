<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = [
        'nombre', 'slug', 'precio',
        'limite_productos', 'limite_usuarios', 'limite_ventas_mes',
        'descripcion', 'orden', 'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'limite_productos' => 'integer',
        'limite_usuarios' => 'integer',
        'limite_ventas_mes' => 'integer',
        'activo' => 'boolean',
    ];

    public function empresas()
    {
        return $this->hasMany(Empresa::class);
    }

    public function esGratis(): bool
    {
        return (float) $this->precio <= 0;
    }

    /** Devuelve el límite de un recurso (null = ilimitado). */
    public function limite(string $recurso): ?int
    {
        return $this->{'limite_' . $recurso} ?? null;
    }
}
