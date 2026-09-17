@extends('admin.layout')

@section('title', 'Tenants')

@section('content')
    <div class="page-head">
        <h1>TENANTS</h1>
        <div class="breadcrumb">Super Admin <span class="sep">/</span> Tenants</div>
    </div>

    <div class="form-card" style="margin-bottom:16px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
            <div class="form-group" style="margin:0">
                <label>Buscar</label>
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Nombre o RUC">
            </div>
            <div class="form-group" style="margin:0">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    @foreach(['trial'=>'En prueba','activa'=>'Activa','suspendida'=>'Suspendida','vencida'=>'Vencida'] as $k=>$v)
                        <option value="{{ $k }}" {{ $estado===$k?'selected':'' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" style="width:auto"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
        </form>
    </div>

    <div class="form-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Negocio</th><th>Plan</th><th>Estado</th>
                    <th style="text-align:center">Usuarios</th>
                    <th style="text-align:center">Productos</th>
                    <th style="text-align:center">Ventas</th>
                    <th style="text-align:right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $t)
                    <tr>
                        <td>
                            <a href="{{ route('admin.tenants.show', $t) }}"><strong>{{ $t->nombre }}</strong></a>
                            @if($t->ruc)<div style="font-size:11px;color:#8a94a0">RUC {{ $t->ruc }}</div>@endif
                        </td>
                        <td>{{ $t->plan->nombre ?? '—' }}</td>
                        <td><span class="st {{ $t->estado_suscripcion }}">{{ $t->estado_suscripcion }}</span></td>
                        <td style="text-align:center">{{ $t->usuarios_count }}</td>
                        <td style="text-align:center">{{ $t->productos_count }}</td>
                        <td style="text-align:center">{{ $t->ventas_count }}</td>
                        <td style="text-align:right;white-space:nowrap">
                            <a href="{{ route('admin.tenants.show', $t) }}" class="btn-icon edit" title="Ver"><i class="fa-solid fa-eye"></i></a>
                            @if($t->estado_suscripcion === 'suspendida')
                                <form method="POST" action="{{ route('admin.tenants.activar', $t) }}" style="display:inline">
                                    @csrf
                                    <button class="btn-icon edit" title="Reactivar"><i class="fa-solid fa-play"></i></button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.tenants.suspender', $t) }}" style="display:inline"
                                      onsubmit="return confirm('¿Suspender a {{ $t->nombre }}?')">
                                    @csrf
                                    <button class="btn-icon del" title="Suspender"><i class="fa-solid fa-ban"></i></button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.tenants.impersonar', $t) }}" style="display:inline"
                                  onsubmit="return confirm('¿Entrar como {{ $t->nombre }}?')">
                                @csrf
                                <button class="btn-icon edit" title="Impersonar"><i class="fa-solid fa-user-secret"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;color:#8a94a0;padding:24px">No hay tenants.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $tenants->links() }}</div>
    </div>
@endsection
