<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    protected $table = 'suscripciones';

    protected $fillable = [
        'empresa_id', 'plan_id', 'estado', 'monto', 'inicia_en', 'termina_en',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'inicia_en' => 'date',
        'termina_en' => 'date',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
