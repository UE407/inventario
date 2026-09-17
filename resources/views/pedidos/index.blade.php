@extends('layouts.app')

@section('title', 'Pedidos de Ruta')

@php
    $moneda = \App\Models\Empresa::actual()->moneda ?? 'Q';
@endphp

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('ventas.index') }}">Ventas</a> / Pedidos de Ruta
    </div>

    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
        <div>
            <h1 class="page-title" style="margin:0;"><i class="fa-solid fa-truck-fast" style="color:#e67e22;"></i> Pedidos de Ruta</h1>
            <p class="page-subtitle" style="margin:4px 0 0 0; color:#64748b;">Toma de pedidos en campo con reserva inmediata de stock y conversión a ventas FEL</p>
        </div>
        <a href="{{ route('pedidos.create') }}" class="btn btn-primary" style="width:auto; padding:10px 18px; font-weight:700;">
            <i class="fa-solid fa-plus-circle"></i> Tomar Pedido de Ruta
        </a>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="stats-grid">
        <div class="card stat-card" style="border-left: 4px solid #f59e0b; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; letter-spacing: 0.5px;">PENDIENTES (RESERVADOS)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #78350f; margin-top: 4px;">{{ $stats['pendientes'] }}</div>
            <div style="font-size: 0.75rem; color: #d97706;">Mercancía apartada en inventario</div>
        </div>
        <div class="card stat-card" style="border-left: 4px solid #10b981; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; letter-spacing: 0.5px;">CONVERTIDOS A VENTA</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #064e3b; margin-top: 4px;">{{ $stats['convertidos'] }}</div>
            <div style="font-size: 0.75rem; color: #10b981;">Facturados / Despachados</div>
        </div>
        <div class="card stat-card" style="border-left: 4px solid #ef4444; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; letter-spacing: 0.5px;">CANCELADOS</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #7f1d1d; margin-top: 4px;">{{ $stats['cancelados'] }}</div>
            <div style="font-size: 0.75rem; color: #ef4444;">Stock liberado</div>
        </div>
        <div class="card stat-card" style="border-left: 4px solid #3b82f6; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; letter-spacing: 0.5px;">TOTAL DE PEDIDOS</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #1e3a8a; margin-top: 4px;">{{ $stats['total'] }}</div>
            <div style="font-size: 0.75rem; color: #3b82f6;">Histórico en ruta</div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="toolbar" style="background:#fff; padding:12px 16px; border-radius:10px; border:1px solid #eef2f6; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
        <form method="GET" action="{{ route('pedidos.index') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; width:100%;">
            <div class="search-box" style="flex:1; min-width:180px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar N° pedido o cliente..." class="form-control">
            </div>

            <div class="search-box no-ico" style="width:160px;">
                <select name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="PENDIENTE" {{ $estado === 'PENDIENTE' ? 'selected' : '' }}>Pendientes</option>
                    <option value="CONVERTIDO" {{ $estado === 'CONVERTIDO' ? 'selected' : '' }}>Convertidos</option>
                    <option value="CANCELADO" {{ $estado === 'CANCELADO' ? 'selected' : '' }}>Cancelados</option>
                </select>
            </div>

            <div class="search-box no-ico" style="width:160px;">
                <select name="ruta_id" class="form-control">
                    <option value="">Todas las rutas</option>
                    @foreach($rutas as $r)
                        <option value="{{ $r->id }}" {{ (string)$rutaId === (string)$r->id ? 'selected' : '' }}>{{ $r->nombre }}</option>
                    @endforeach
                </select>
            </div>

            @if(auth()->user()->rol === 'admin')
            <div class="search-box no-ico" style="width:160px;">
                <select name="vendedor_id" class="form-control">
                    <option value="">Todos los vendedores</option>
                    @foreach($vendedores as $v)
                        <option value="{{ $v->id }}" {{ (string)$vendedorId === (string)$v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="search-box no-ico" style="width:130px;">
                <input type="date" name="desde" value="{{ $desde }}" title="Desde" class="form-control">
            </div>

            <div class="search-box no-ico" style="width:130px;">
                <input type="date" name="hasta" value="{{ $hasta }}" title="Hasta" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary btn-sm" style="height:38px; padding:0 16px; white-space:nowrap; flex-shrink:0;"><i class="fa-solid fa-filter"></i> Filtrar</button>
            @if($q || $estado || $vendedorId || $rutaId || $desde || $hasta)
                <a href="{{ route('pedidos.index') }}" class="btn btn-light btn-sm" style="height:38px; padding:0 16px; white-space:nowrap; flex-shrink:0;"><i class="fa-solid fa-xmark"></i> Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Tabla de Pedidos -->
    <div class="card" style="background:white; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.04); overflow:hidden;">
        <div class="table-responsive">
            <table class="table" style="margin:0;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="padding:12px 16px;">N° PEDIDO</th>
                        <th>FECHA TOMA</th>
                        <th>CLIENTE</th>
                        <th>RUTA DE ENTREGA</th>
                        <th>VENDEDOR</th>
                        <th>TOTAL</th>
                        <th>ESTADO</th>
                        <th style="text-align:right; padding-right:16px;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pedidos as $p)
                    <tr>
                        <td style="padding:12px 16px;">
                            <strong style="color:var(--brand); font-size:0.95rem;">{{ $p->numero }}</strong>
                        </td>
                        <td>{{ $p->fecha_pedido ? $p->fecha_pedido->format('d/m/Y H:i') : '-' }}</td>
                        <td>
                            <div style="font-weight:600; color:#1e293b;">{{ $p->cliente->nombre ?? 'Consumidor Final' }}</div>
                            @if($p->cliente && $p->cliente->numero_documento)
                                <small style="color:#64748b;">{{ $p->cliente->tipo_documento }}: {{ $p->cliente->numero_documento }}</small>
                            @endif
                        </td>
                        <td>
                            @if($p->ruta || ($p->cliente && $p->cliente->ruta))
                                <span class="badge" style="background:#f0f9ff; color:#0369a1; font-weight:600;">
                                    <i class="fa-solid fa-route"></i> {{ $p->ruta->nombre ?? $p->cliente->ruta->nombre }}
                                </span>
                            @else
                                <span style="color:#94a3b8; font-size:0.85rem;">— Sin Ruta —</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-weight:500; color:#334155;"><i class="fa-solid fa-user-tag" style="color:#94a3b8;"></i> {{ $p->vendedor->name ?? 'Sistema' }}</span>
                        </td>
                        <td>
                            <strong style="font-size:1.05rem; color:#0f172a;">{{ $moneda }} {{ number_format($p->total, 2) }}</strong>
                        </td>
                        <td>
                            @if($p->estado === 'PENDIENTE')
                                <span class="badge" style="background:#fef3c7; color:#92400e; font-weight:700; padding:5px 10px; border-radius:20px;">
                                    <i class="fa-solid fa-hourglass-half"></i> Pendiente (Reservado)
                                </span>
                            @elseif($p->estado === 'CONVERTIDO')
                                <span class="badge" style="background:#d1fae5; color:#065f46; font-weight:700; padding:5px 10px; border-radius:20px;">
                                    <i class="fa-solid fa-circle-check"></i> Convertido a Venta
                                </span>
                            @else
                                <span class="badge" style="background:#fee2e2; color:#991b1b; font-weight:700; padding:5px 10px; border-radius:20px;">
                                    <i class="fa-solid fa-ban"></i> Cancelado
                                </span>
                            @endif
                        </td>
                        <td style="text-align:right; padding-right:16px;">
                            <a href="{{ route('pedidos.show', $p) }}" class="btn btn-light" style="width:auto; padding:6px 12px; font-size:0.85rem;">
                                <i class="fa-solid fa-eye"></i> Ver / Procesar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px 20px; color:#64748b;">
                            <i class="fa-solid fa-truck-ramp-box" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:10px;"></i><br>
                            <strong>No se encontraron pedidos de ruta en este periodo.</strong>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($pedidos->hasPages())
            <div style="padding:15px; border-top:1px solid #e2e8f0;">
                {{ $pedidos->links() }}
            </div>
        @endif
    </div>
@endsection
