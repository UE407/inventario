<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuración global de la plataforma (fila única).
 */
class PlataformaConfig extends Model
{
    protected $table = 'plataforma_configs';

    protected $fillable = [
        'nombre_saas', 'logo', 'dias_trial', 'correo_soporte', 'moneda', 'mensaje_bienvenida',
    ];

    protected $casts = [
        'dias_trial' => 'integer',
    ];

    protected static ?self $cache = null;

    /** Devuelve la configuración (la crea con valores por defecto si no existe). */
    public static function actual(): self
    {
        if (static::$cache) {
            return static::$cache;
        }
        return static::$cache = static::query()->firstOr(fn () => static::create([]));
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::$cache = null);
    }
}
