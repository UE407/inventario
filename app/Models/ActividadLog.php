<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActividadLog extends Model
{
    protected $table = 'actividad_logs';

    protected $fillable = ['user_id', 'empresa_id', 'accion', 'descripcion'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Registra una acción del super admin en la bitácora. */
    public static function registrar(string $accion, ?string $descripcion = null, ?int $empresaId = null): void
    {
        static::create([
            'user_id' => Auth::id(),
            'empresa_id' => $empresaId,
            'accion' => $accion,
            'descripcion' => $descripcion,
        ]);
    }
}
