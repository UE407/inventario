<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Lote extends Model
{
    use BelongsToEmpresa;

    protected $table = 'lotes';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'codigo_lote',
        'fecha_vencimiento',
        'stock_inicial',
        'stock_actual',
        'estado',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'stock_inicial' => 'integer',
        'stock_actual' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function scopeVigentes($query)
    {
        return $query->where('stock_actual', '>', 0)
                    ->whereDate('fecha_vencimiento', '>', Carbon::today());
    }

    public function scopePorVencer($query, int $dias = 30)
    {
        $today = Carbon::today();
        $limitDate = Carbon::today()->addDays($dias);

        return $query->where('stock_actual', '>', 0)
                    ->whereDate('fecha_vencimiento', '>', $today)
                    ->whereDate('fecha_vencimiento', '<=', $limitDate);
    }

    public function scopeVencidos($query)
    {
        return $query->where('stock_actual', '>', 0)
                    ->whereDate('fecha_vencimiento', '<=', Carbon::today());
    }

    public function scopeFefoOrder($query)
    {
        return $query->where('stock_actual', '>', 0)
                    ->whereDate('fecha_vencimiento', '>', Carbon::today())
                    ->orderBy('fecha_vencimiento', 'asc');
    }

    /**
     * Calcula dinámicamente el estado del lote según la fecha actual y stock.
     */
    public function getEstadoCalculadoAttribute(): string
    {
        if ($this->stock_actual <= 0) {
            return 'AGOTADO';
        }

        if (!$this->fecha_vencimiento) {
            return 'VIGENTE';
        }

        $today = Carbon::today();

        if ($this->fecha_vencimiento->isPast() || $this->fecha_vencimiento->isSameDay($today)) {
            return 'VENCIDO';
        }

        $diasAlerta = ($this->producto && $this->producto->dias_alerta_vencimiento)
            ? (int) $this->producto->dias_alerta_vencimiento
            : 30;

        $diasRestantes = (int) $today->diffInDays($this->fecha_vencimiento, false);

        if ($diasRestantes > 0 && $diasRestantes <= $diasAlerta) {
            return 'POR_VENCER';
        }

        return 'VIGENTE';
    }
}
