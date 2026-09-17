@extends('admin.layout')

@section('title', 'Resumen')

@php
    $monedaSaas = \App\Models\PlataformaConfig::actual()->moneda ?? 'Q';
@endphp

@section('content')
    <div class="page-head">
        <h1>RESUMEN DE LA PLATAFORMA</h1>
        <div class="breadcrumb">Super Admin <span class="sep">/</span> Resumen</div>
    </div>

    <div class="stat-grid">
        <div class="stat"><div class="l">Tenants</div><div class="n">{{ $totalTenants }}</div></div>
        <div class="stat"><div class="l">Activos</div><div class="n" style="color:#1e9e5a">{{ $activos }}</div></div>
        <div class="stat"><div class="l">En prueba</div><div class="n" style="color:#1560a8">{{ $enTrial }}</div></div>
        <div class="stat"><div class="l">Suspendidos</div><div class="n" style="color:#c0392b">{{ $suspendidos }}</div></div>
        <div class="stat"><div class="l">Usuarios</div><div class="n">{{ $totalUsuarios }}</div></div>
        <div class="stat"><div class="l">MRR estimado</div><div class="n">{{ $monedaSaas }} {{ number_format($mrr, 2) }}</div></div>
    </div>

    <div class="grid-2">
        <div class="form-card">
            <h3 style="margin:0 0 12px;font-size:15px">Altas de tenants (6 meses)</h3>
            <table class="table">
                <thead><tr><th>Mes</th><th style="text-align:right">Nuevos</th></tr></thead>
                <tbody>
                    @foreach($altas as $a)
                        <tr><td>{{ $a['label'] }}</td><td style="text-align:right">{{ $a['total'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-card">
            <h3 style="margin:0 0 12px;font-size:15px">Pruebas por vencer (7 días)</h3>
            @if($trialsPorVencer->isEmpty())
                <p style="color:#8a94a0;font-size:13px">Ninguna prueba vence esta semana.</p>
            @else
                <table class="table">
                    <thead><tr><th>Tenant</th><th>Plan</th><th>Vence</th></tr></thead>
                    <tbody>
                        @foreach($trialsPorVencer as $t)
                            <tr>
                                <td><a href="{{ route('admin.tenants.show', $t) }}">{{ $t->nombre }}</a></td>
                                <td>{{ $t->plan->nombre ?? '—' }}</td>
                                <td>{{ $t->trial_termina_en->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="grid-2">
        <div class="form-card">
            <h3 style="margin:0 0 12px;font-size:15px">Últimos tenants</h3>
            <table class="table">
                <thead><tr><th>Negocio</th><th>Plan</th><th>Estado</th></tr></thead>
                <tbody>
                    @foreach($ultimosTenants as $t)
                        <tr>
                            <td><a href="{{ route('admin.tenants.show', $t) }}">{{ $t->nombre }}</a></td>
                            <td>{{ $t->plan->nombre ?? '—' }}</td>
                            <td><span class="st {{ $t->estado_suscripcion }}">{{ $t->estado_suscripcion }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-card">
            <h3 style="margin:0 0 12px;font-size:15px">Suscripciones recientes</h3>
            <table class="table">
                <thead><tr><th>Tenant</th><th>Plan</th><th>Estado</th><th style="text-align:right">Monto</th></tr></thead>
                <tbody>
                    @foreach($ultimasSuscripciones as $sus)
                        <tr>
                            <td>{{ $sus->empresa->nombre ?? '—' }}</td>
                            <td>{{ $sus->plan->nombre ?? '—' }}</td>
                            <td><span class="st {{ $sus->estado }}">{{ $sus->estado }}</span></td>
                            <td style="text-align:right">{{ $monedaSaas }} {{ number_format($sus->monto, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
