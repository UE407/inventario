<?php

namespace App\Models;

use App\Support\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Empresa = tenant. Cada fila representa un negocio con sus propios datos,
 * su plan y el estado de su suscripción.
 */
class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre', 'ruc', 'direccion', 'telefono', 'email', 'moneda', 'igv', 'logo',
        'plan_id', 'estado_suscripcion', 'trial_termina_en', 'suscripcion_termina_en',
    ];

    protected $casts = [
        'igv' => 'decimal:2',
        'trial_termina_en' => 'date',
        'suscripcion_termina_en' => 'date',
    ];

    /** Cache en memoria durante el request para evitar consultas repetidas. */
    protected static ?self $cache = null;

    /** Usuarios que pertenecen a esta empresa. */
    public function usuarios()
    {
        return $this->hasMany(User::class);
    }

    /** Plan contratado actualmente. */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /** Historial de suscripciones. */
    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class);
    }

    /** Productos del tenant (para conteos del panel super admin). */
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    /** Ventas del tenant. */
    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    // ------------------------------------------------------------------
    // Estado de la suscripción
    // ------------------------------------------------------------------

    /** ¿Está en periodo de prueba y aún no vence? */
    public function enTrial(): bool
    {
        return $this->estado_suscripcion === 'trial'
            && $this->trial_termina_en
            && $this->trial_termina_en->endOfDay()->isFuture();
    }

    /** Días restantes del periodo de prueba (0 si no aplica). */
    public function diasTrialRestantes(): int
    {
        if ($this->estado_suscripcion !== 'trial' || ! $this->trial_termina_en) {
            return 0;
        }
        return max(0, Carbon::today()->diffInDays($this->trial_termina_en, false));
    }

    /**
     * ¿La empresa puede usar la aplicación?
     * True si está en trial vigente o con suscripción activa no vencida.
     */
    public function suscripcionVigente(): bool
    {
        if ($this->enTrial()) {
            return true;
        }

        if ($this->estado_suscripcion === 'activa') {
            // Sin fecha de fin (p. ej. plan gratis) o fecha aún futura.
            return ! $this->suscripcion_termina_en
                || $this->suscripcion_termina_en->endOfDay()->isFuture();
        }

        return false;
    }

    /** Suspende el acceso del tenant (impago, abuso, etc.). */
    public function suspender(): void
    {
        $this->update(['estado_suscripcion' => 'suspendida']);
    }

    /** Reactiva el tenant: activa con vencimiento a 1 mes (o sin fin si es gratis). */
    public function reactivar(): void
    {
        $termina = optional($this->plan)->esGratis() === false && $this->plan
            ? \Illuminate\Support\Carbon::today()->addMonth()
            : null;

        $this->update([
            'estado_suscripcion' => 'activa',
            'suscripcion_termina_en' => $termina,
        ]);
    }

    // ------------------------------------------------------------------
    // Límites del plan (feature gating)
    // ------------------------------------------------------------------

    /** Límite de un recurso según el plan (null = ilimitado / sin plan). */
    public function limite(string $recurso): ?int
    {
        return $this->plan?->limite($recurso);
    }

    /** Uso actual de un recurso dentro de esta empresa. */
    public function uso(string $recurso): int
    {
        return Tenant::withTenant($this->getKey(), function () use ($recurso) {
            return match ($recurso) {
                'productos' => Producto::count(),
                'usuarios' => User::count(),
                'ventas_mes' => Venta::where('estado', 'COMPLETADA')
                    ->where('created_at', '>=', Carbon::now()->startOfMonth())
                    ->count(),
                default => 0,
            };
        });
    }

    /** ¿Se alcanzó (o superó) el límite del recurso? */
    public function limiteAlcanzado(string $recurso): bool
    {
        $limite = $this->limite($recurso);
        if ($limite === null) {
            return false; // ilimitado
        }
        return $this->uso($recurso) >= $limite;
    }

    // ------------------------------------------------------------------

    /**
     * Devuelve la configuración de la EMPRESA ACTIVA (el tenant del usuario
     * autenticado). Si no hay tenant en contexto (p. ej. consola o pantalla
     * de invitado) cae a la primera empresa, o crea una por defecto.
     */
    public static function actual(): self
    {
        $id = Tenant::id();

        if ($id) {
            if (static::$cache && static::$cache->getKey() === $id) {
                return static::$cache;
            }
            $empresa = static::query()->find($id);
            if ($empresa) {
                return static::$cache = $empresa;
            }
        }

        if (static::$cache) {
            return static::$cache;
        }

        return static::$cache = static::query()->firstOr(function () {
            return static::create(['nombre' => config('app.name', 'Mi Empresa')]);
        });
    }

    protected static function booted(): void
    {
        // Invalida la cache si la configuración se actualiza.
        static::saved(fn () => static::$cache = null);
    }

    /** Tasa de IGV como fracción (0.18) */
    public function tasaIgv(): float
    {
        return (float) $this->igv / 100;
    }
}
