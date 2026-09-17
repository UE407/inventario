@extends('admin.layout')

@section('title', $tenant->nombre)

@section('content')
    <div class="page-head">
        <h1>{{ strtoupper($tenant->nombre) }}</h1>
        <div class="breadcrumb">
            <a href="{{ route('admin.tenants.index') }}">Tenants</a>
            <span class="sep">/</span> {{ $tenant->nombre }}
        </div>
    </div>

    <div class="grid-2">
        {{-- Datos y estado --}}
        <div class="form-card">
            <h3 style="margin:0 0 12px;font-size:15px">Estado de suscripción</h3>
            <p style="margin:4px 0"><strong>Plan:</strong> {{ $tenant->plan->nombre ?? '—' }}
                ({{ $tenant->moneda }} {{ number_format($tenant->plan->precio ?? 0, 2) }}/mes)</p>
            <p style="margin:4px 0"><strong>Estado:</strong>
                <span class="st {{ $tenant->estado_suscripcion }}">{{ $tenant->estado_suscripcion }}</span></p>
            @if($tenant->trial_termina_en)
                <p style="margin:4px 0"><strong>Prueba hasta:</strong> {{ $tenant->trial_termina_en->format('d/m/Y') }}</p>
            @endif
            @if($tenant->suscripcion_termina_en)
                <p style="margin:4px 0"><strong>Renueva:</strong> {{ $tenant->suscripcion_termina_en->format('d/m/Y') }}</p>
            @endif
            @if($tenant->ruc)<p style="margin:4px 0"><strong>RUC:</strong> {{ $tenant->ruc }}</p>@endif
            @if($tenant->email)<p style="margin:4px 0"><strong>Email:</strong> {{ $tenant->email }}</p>@endif

            <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
                @if($tenant->estado_suscripcion === 'suspendida')
                    <form method="POST" action="{{ route('admin.tenants.activar', $tenant) }}">
                        @csrf
                        <button class="btn btn-success btn-sm" style="width:auto"><i class="fa-solid fa-play"></i> Reactivar</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.tenants.suspender', $tenant) }}"
                          onsubmit="return confirm('¿Suspender este tenant?')">
                        @csrf
                        <button class="btn btn-warning btn-sm" style="width:auto"><i class="fa-solid fa-ban"></i> Suspender</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.tenants.impersonar', $tenant) }}"
                      onsubmit="return confirm('¿Entrar como este tenant?')">
                    @csrf
                    <button class="btn btn-light btn-sm" style="width:auto"><i class="fa-solid fa-user-secret"></i> Impersonar</button>
                </form>
                <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}"
                      onsubmit="return confirm('ELIMINAR el tenant y TODOS sus datos. ¿Continuar?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm" style="width:auto"><i class="fa-solid fa-trash"></i> Eliminar</button>
                </form>
            </div>
        </div>

        {{-- Cambiar plan + uso --}}
        <div class="form-card">
            <h3 style="margin:0 0 12px;font-size:15px">Uso y plan</h3>
            @foreach(['productos'=>'Productos','usuarios'=>'Usuarios','ventas_mes'=>'Ventas del mes'] as $k=>$label)
                @php $lim = $tenant->limite($k); @endphp
                <p style="margin:4px 0;font-size:13px">{{ $label }}: <strong>{{ $uso[$k] }}</strong> / {{ $lim === null ? '∞' : $lim }}</p>
            @endforeach

            <form method="POST" action="{{ route('admin.tenants.plan', $tenant) }}" style="margin-top:12px">
                @csrf @method('PUT')
                <div class="form-group">
                    <label>Cambiar plan</label>
                    <select name="plan_id" class="form-control">
                        @foreach($planes as $p)
                            <option value="{{ $p->id }}" {{ $tenant->plan_id===$p->id?'selected':'' }}>
                                {{ $p->nombre }} — {{ $tenant->moneda }} {{ number_format($p->precio,2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" style="width:auto"><i class="fa-solid fa-arrows-rotate"></i> Aplicar plan</button>
            </form>
        </div>
    </div>

    <div class="grid-2">
        <div class="form-card">
            <h3 style="margin:0 0 12px;font-size:15px">Usuarios del tenant</h3>
            <table class="table">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Activo</th></tr></thead>
                <tbody>
                    @forelse($usuarios as $u)
                        <tr>
                            <td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->rol }}</td>
                            <td>{!! $u->activo ? '<span class="st activa">sí</span>' : '<span class="st suspendida">no</span>' !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;color:#8a94a0">Sin usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="form-card">
            <h3 style="margin:0 0 12px;font-size:15px">Historial de suscripciones</h3>
            <table class="table">
                <thead><tr><th>Plan</th><th>Estado</th><th>Desde</th><th style="text-align:right">Monto</th></tr></thead>
                <tbody>
                    @forelse($suscripciones as $s)
                        <tr>
                            <td>{{ $s->plan->nombre ?? '—' }}</td>
                            <td><span class="st {{ $s->estado }}">{{ $s->estado }}</span></td>
                            <td>{{ optional($s->inicia_en)->format('d/m/Y') }}</td>
@php $monedaSaas = \App\Models\PlataformaConfig::actual()->moneda ?? 'Q'; @endphp
                            <td style="text-align:right">{{ $monedaSaas }} {{ number_format($s->monto,2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;color:#8a94a0">Sin registros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
