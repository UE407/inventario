@extends('admin.layout')

@section('title', 'Reportes')

@php
    $monedaSaas = \App\Models\PlataformaConfig::actual()->moneda ?? 'Q';
@endphp

@section('content')
    <div class="page-head">
        <h1>REPORTES DE NEGOCIO</h1>
        <div class="breadcrumb">Super Admin <span class="sep">/</span> Reportes</div>
    </div>

    <div style="margin-bottom:14px">
        <a href="{{ route('admin.reportes.export') }}" class="btn btn-light btn-sm" style="width:auto">
            <i class="fa-solid fa-file-excel" style="color:#1e7e45"></i> Exportar (12 meses)
        </a>
    </div>

    <div class="stat-grid">
        <div class="stat"><div class="l">Tenants</div><div class="n">{{ $totalTenants }}</div></div>
        <div class="stat"><div class="l">Activos</div><div class="n" style="color:#1e9e5a">{{ $porEstado['activa'] ?? 0 }}</div></div>
        <div class="stat"><div class="l">En prueba</div><div class="n" style="color:#1560a8">{{ $porEstado['trial'] ?? 0 }}</div></div>
        <div class="stat"><div class="l">Suspendidos</div><div class="n" style="color:#c0392b">{{ $porEstado['suspendida'] ?? 0 }}</div></div>
        <div class="stat"><div class="l">MRR</div><div class="n">{{ $monedaSaas }} {{ number_format($mrr,2) }}</div></div>
    </div>

    <div class="grid-2">
        <div class="panel">
            <h3 style="margin:0 0 12px;font-size:15px">MRR por plan</h3>
            <table class="table">
                <thead><tr><th>Plan</th><th style="text-align:center">Activos</th><th style="text-align:right">Precio</th><th style="text-align:right">MRR</th></tr></thead>
                <tbody>
                    @foreach($porPlan as $r)
                        <tr>
                            <td>{{ $r['plan'] }}</td>
                            <td style="text-align:center">{{ $r['activos'] }}</td>
                            <td style="text-align:right">{{ $monedaSaas }} {{ number_format($r['precio'],2) }}</td>
                            <td style="text-align:right"><strong>{{ $monedaSaas }} {{ number_format($r['mrr'],2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h3 style="margin:0 0 12px;font-size:15px">Últimos 12 meses</h3>
            <table class="table">
                <thead><tr><th>Mes</th><th style="text-align:center">Nuevos</th><th style="text-align:right">Ingresos</th></tr></thead>
                <tbody>
                    @foreach($serie as $m)
                        <tr>
                            <td>{{ $m['label'] }}</td>
                            <td style="text-align:center">{{ $m['altas'] }}</td>
                            <td style="text-align:right">{{ $monedaSaas }} {{ number_format($m['ingresos'],2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
