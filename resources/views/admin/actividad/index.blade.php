@extends('admin.layout')

@section('title', 'Actividad')

@section('content')
    <div class="page-head">
        <h1>REGISTRO DE ACTIVIDAD</h1>
        <div class="breadcrumb">Super Admin <span class="sep">/</span> Actividad</div>
    </div>

    <div class="toolbar" style="margin-bottom:16px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar en la descripción...">
            </div>
            <div class="search-box no-ico">
                <select name="accion">
                    <option value="">Todas las acciones</option>
                    @foreach($acciones as $a)
                        <option value="{{ $a }}" {{ $accion===$a?'selected':'' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary btn-sm" style="width:auto"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </form>
    </div>

    <div class="panel">
        <div class="panel-scroll">
        <table class="table">
            <thead>
                <tr><th>Fecha</th><th>Autor</th><th>Acción</th><th>Detalle</th><th>Tenant</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="white-space:nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->usuario->name ?? '—' }}</td>
                        <td><span class="badge-soft">{{ $log->accion }}</span></td>
                        <td>{{ $log->descripcion }}</td>
                        <td>
                            @if($log->empresa)
                                <a href="{{ route('admin.tenants.show', $log->empresa_id) }}">{{ $log->empresa->nombre }}</a>
                            @else — @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;color:#9aa3ab;padding:24px">Sin actividad registrada.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div style="margin-top:14px">{{ $logs->links() }}</div>
    </div>
@endsection
