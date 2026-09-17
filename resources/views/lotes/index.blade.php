@extends('layouts.app')

@section('title', 'Control de Lotes y Fechas de Vencimiento')

@section('content')
<div class="breadcrumb">
    <a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('productos.index') }}">Inventario</a> / Lotes y Vencimientos
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-calendar-xmark" style="color: #e67e22"></i> Control de Lotes y Vencimientos</h1>
        <p class="page-subtitle">Gestión de productos perecederos mediante la estrategia FEFO (Primero en Vencer, Primero en Salir)</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('modalNuevoLote').style.display='flex'">
        <i class="fa-solid fa-plus"></i> Registrar Nuevo Lote
    </button>
</div>

<!-- Tarjetas de resumen de estados -->
<div class="stats-grid">
    <div class="card stat-card" style="border-left: 4px solid #10b981; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">VIGENTES</div>
        <div style="font-size: 1.8rem; font-weight: 700; color: #064e3b; margin-top: 5px;">{{ $stats['vigentes'] }}</div>
        <div style="font-size: 0.75rem; color: #10b981;">Disponibles para venta</div>
    </div>
    <div class="card stat-card" style="border-left: 4px solid #f59e0b; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">PRÓXIMOS A VENCER</div>
        <div style="font-size: 1.8rem; font-weight: 700; color: #78350f; margin-top: 5px;">{{ $stats['por_vencer'] }}</div>
        <div style="font-size: 0.75rem; color: #f59e0b;">Prioridad de salida (FEFO)</div>
    </div>
    <div class="card stat-card" style="border-left: 4px solid #ef4444; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">VENCIDOS</div>
        <div style="font-size: 1.8rem; font-weight: 700; color: #7f1d1d; margin-top: 5px;">{{ $stats['vencidos'] }}</div>
        <div style="font-size: 0.75rem; color: #ef4444;">Bloqueados para venta</div>
    </div>
    <div class="card stat-card" style="border-left: 4px solid #94a3b8; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">AGOTADOS</div>
        <div style="font-size: 1.8rem; font-weight: 700; color: #334155; margin-top: 5px;">{{ $stats['agotados'] }}</div>
        <div style="font-size: 0.75rem; color: #64748b;">Stock en cero</div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 20px; padding: 15px;">
    <form method="GET" action="{{ route('lotes.index') }}" class="search-form" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por código de lote o producto..." class="form-control" style="flex: 1; min-width: 200px;">
        
        <select name="estado" class="form-control" style="width: auto;">
            <option value="">Todos los estados</option>
            <option value="VIGENTE" {{ $estado === 'VIGENTE' ? 'selected' : '' }}>Vigentes</option>
            <option value="POR_VENCER" {{ $estado === 'POR_VENCER' ? 'selected' : '' }}>Próximos a Vencer</option>
            <option value="VENCIDO" {{ $estado === 'VENCIDO' ? 'selected' : '' }}>Vencidos</option>
            <option value="AGOTADO" {{ $estado === 'AGOTADO' ? 'selected' : '' }}>Agotados</option>
        </select>

        <select name="producto_id" class="form-control" style="width: auto;">
            <option value="">Todos los productos perecederos</option>
            @foreach($productos as $p)
                <option value="{{ $p->id }}" {{ (string)$productoId === (string)$p->id ? 'selected' : '' }}>{{ $p->nombre }} ({{ $p->codigo }})</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
        @if($q || $estado || $productoId)
            <a href="{{ route('lotes.index') }}" class="btn btn-light"><i class="fa-solid fa-xmark"></i> Limpiar</a>
        @endif
    </form>
</div>

<!-- Tabla de Lotes -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>CÓDIGO LOTE</th>
                    <th>PRODUCTO</th>
                    <th>FECHA VENCIMIENTO</th>
                    <th>DÍAS RESTANTES</th>
                    <th>STOCK INICIAL</th>
                    <th>STOCK ACTUAL</th>
                    <th>ESTADO FEFO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lotes as $lote)
                    @php
                        $today = \Carbon\Carbon::today();
                        $dias = $lote->fecha_vencimiento ? (int)$today->diffInDays($lote->fecha_vencimiento, false) : 0;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $lote->codigo_lote }}</strong>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $lote->producto->nombre ?? 'Desconocido' }}</div>
                            <small class="text-muted">Cód: {{ $lote->producto->codigo ?? '-' }}</small>
                        </td>
                        <td>
                            <i class="fa-regular fa-calendar"></i> {{ $lote->fecha_vencimiento ? $lote->fecha_vencimiento->format('d/m/Y') : '-' }}
                        </td>
                        <td>
                            @if($dias < 0)
                                <span style="color: #ef4444; font-weight: 700;">Vencido hace {{ abs($dias) }} días</span>
                            @elseif($dias == 0)
                                <span style="color: #ef4444; font-weight: 700;">Vence hoy</span>
                            @elseif($dias <= ($lote->producto->dias_alerta_vencimiento ?? 30))
                                <span style="color: #d97706; font-weight: 700;">{{ $dias }} días restantes</span>
                            @else
                                <span style="color: #10b981; font-weight: 600;">{{ $dias }} días</span>
                            @endif
                        </td>
                        <td>{{ $lote->stock_inicial }} {{ $lote->producto->unidad ?? 'UND' }}</td>
                        <td>
                            <strong style="font-size: 1.05rem;">{{ $lote->stock_actual }}</strong> {{ $lote->producto->unidad ?? 'UND' }}
                        </td>
                        <td>
                            @if($lote->estado === 'VIGENTE')
                                <span class="badge" style="background:#d1fae5; color:#065f46; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Vigente</span>
                            @elseif($lote->estado === 'POR_VENCER')
                                <span class="badge" style="background:#fef3c7; color:#92400e; font-weight:700;"><i class="fa-solid fa-triangle-exclamation"></i> Próximo a vencer</span>
                            @elseif($lote->estado === 'VENCIDO')
                                <span class="badge" style="background:#fee2e2; color:#991b1b; font-weight:700;"><i class="fa-solid fa-ban"></i> Vencido (Bloqueado)</span>
                            @else
                                <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600;">Agotado</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 30px; color: #64748b;">
                            <i class="fa-solid fa-boxes-stacked" style="font-size: 2rem; margin-bottom: 10px; color: #cbd5e1;"></i><br>
                            No se encontraron lotes registrados con los criterios seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($lotes->hasPages())
        <div style="padding: 15px;">
            {{ $lotes->links() }}
        </div>
    @endif
</div>

<!-- Modal Registrar Lote -->
<div id="modalNuevoLote" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center;">
    <div class="modal-card" style="background: white; border-radius: 12px; padding: 25px; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin:0; font-size: 1.2rem;"><i class="fa-solid fa-plus-circle" style="color: #3b82f6;"></i> Registrar Lote Manual</h3>
            <button type="button" onclick="document.getElementById('modalNuevoLote').style.display='none'" style="background:none; border:none; font-size: 1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="{{ route('lotes.store') }}">
            @csrf
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Producto Perecedero <span style="color:#e74c3c">*</span></label>
                <select name="producto_id" class="form-control" required>
                    <option value="">Seleccione producto...</option>
                    @foreach($productos as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->codigo }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label>Código o Número de Lote <span style="color:#e74c3c">*</span></label>
                <input type="text" name="codigo_lote" class="form-control" placeholder="Ej: LOTE-2026-001" required>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label>Fecha de Vencimiento <span style="color:#e74c3c">*</span></label>
                <input type="date" name="fecha_vencimiento" class="form-control" required min="{{ date('Y-m-d') }}">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>Cantidad Inicial de Stock <span style="color:#e74c3c">*</span></label>
                <input type="number" name="stock_inicial" class="form-control" min="1" required placeholder="Ej: 50">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-light" onclick="document.getElementById('modalNuevoLote').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar Lote</button>
            </div>
        </form>
    </div>
</div>
@endsection
