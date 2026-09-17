@extends('layouts.app')

@section('title', 'Rutas de Entrega')

@section('content')
    <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
        <div>
            <h1><i class="fa-solid fa-route"></i> Rutas de Entrega</h1>
            <div style="font-size:0.85rem; color:#64748b; margin-top:2px;">
                Gestión de rutas de distribución y configuración de días estimados de entrega.
            </div>
        </div>
        <a href="{{ route('rutas.create') }}" class="btn btn-primary" style="white-space:nowrap;">
            <i class="fa-solid fa-plus"></i> Nueva Ruta
        </a>
    </div>

    <!-- Barra de Filtros -->
    <div class="toolbar" style="background:#fff; padding:12px 16px; border-radius:10px; border:1px solid #eef2f6; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
        <form method="GET" action="{{ route('rutas.index') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; width:100%;">
            <div class="search-box" style="flex:1; min-width:200px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nombre o código de ruta..." class="form-control">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="height:38px; padding:0 16px;"><i class="fa-solid fa-filter"></i> Filtrar</button>
            @if($q)
                <a href="{{ route('rutas.index') }}" class="btn btn-light btn-sm" style="height:38px; padding:0 16px;"><i class="fa-solid fa-xmark"></i> Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Tabla de Rutas -->
    <div class="card" style="background:white; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.04); overflow:hidden;">
        <div class="table-responsive">
            <table class="table" style="margin:0;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th>Código</th>
                        <th>Nombre de Ruta</th>
                        <th>Días Estimados Entrega</th>
                        <th>Clientes Asignados</th>
                        <th>Pedidos Realizados</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rutas as $r)
                    <tr>
                        <td><span class="badge-soft">{{ $r->codigo ?: 'RUT-'.$r->id }}</span></td>
                        <td>
                            <strong>{{ $r->nombre }}</strong>
                            @if($r->descripcion)
                                <br><small style="color:#64748b;">{{ Str::limit($r->descripcion, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700;">
                                <i class="fa-solid fa-clock"></i> {{ $r->dias_entrega_estimados }} {{ $r->dias_entrega_estimados == 1 ? 'día' : 'días' }}
                            </span>
                        </td>
                        <td>{{ $r->clientes_count }} clientes</td>
                        <td>{{ $r->pedidos_count }} pedidos</td>
                        <td>
                            @if($r->activo)
                                <span class="badge-stock ok">Activa</span>
                            @else
                                <span class="badge-stock out">Inactiva</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            <a href="{{ route('rutas.edit', $r) }}" class="btn-icon edit" title="Editar Ruta"><i class="fa-solid fa-pen"></i></a>
                            @if($r->pedidos_count == 0)
                                <form action="{{ route('rutas.destroy', $r) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta ruta?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon del" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">
                            <i class="fa-solid fa-route" style="font-size:2rem; margin-bottom:10px; opacity:0.5;"></i>
                            <br>No hay rutas de entrega registradas.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($rutas->hasPages())
            <div style="padding:15px; border-top:1px solid #eef2f6;">
                {{ $rutas->links() }}
            </div>
        @endif
    </div>
@endsection
