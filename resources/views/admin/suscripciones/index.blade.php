@extends('admin.layout')

@section('title', 'Suscripciones')

@php
    $monedaSaas = \App\Models\PlataformaConfig::actual()->moneda ?? 'Q';
@endphp

@section('content')
    <div class="page-head">
        <h1>SUSCRIPCIONES</h1>
        <div class="breadcrumb">Super Admin <span class="sep">/</span> Suscripciones</div>
    </div>

    <div class="toolbar" style="margin-bottom:16px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="{{ $q }}" placeholder="Negocio...">
            </div>
            <div class="search-box no-ico">
                <select name="estado">
                    <option value="">Todos los estados</option>
                    @foreach(['trial'=>'Prueba','activa'=>'Activa','vencida'=>'Vencida','cancelada'=>'Cancelada'] as $k=>$v)
                        <option value="{{ $k }}" {{ $estado===$k?'selected':'' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="search-box no-ico">
                <select name="plan">
                    <option value="">Todos los planes</option>
                    @foreach($planes as $p)
                        <option value="{{ $p->id }}" {{ (string)$planId===(string)$p->id?'selected':'' }}>{{ $p->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary btn-sm" style="width:auto"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </form>
        <div class="spacer"></div>
        <a href="{{ route('admin.suscripciones.export', request()->only('q','estado','plan')) }}" class="btn btn-light btn-sm" style="width:auto">
            <i class="fa-solid fa-file-excel" style="color:#1e7e45"></i> Exportar
        </a>
    </div>

    <div class="panel">
        <div class="panel-scroll">
        <table class="table">
            <thead>
                <tr><th>Fecha</th><th>Negocio</th><th>Plan</th><th>Estado</th>
                    <th style="text-align:right">Monto</th><th>Inicia</th><th>Termina</th></tr>
            </thead>
            <tbody>
                @forelse($suscripciones as $s)
                    <tr>
                        <td>{{ $s->created_at->format('d/m/Y') }}</td>
                        <td><a href="{{ route('admin.tenants.show', $s->empresa_id) }}">{{ $s->empresa->nombre ?? '—' }}</a></td>
                        <td>{{ $s->plan->nombre ?? '—' }}</td>
                        <td><span class="st {{ $s->estado==='activa'?'activa':($s->estado==='trial'?'trial':'suspendida') }}">{{ $s->estado }}</span></td>
                        <td style="text-align:right">{{ $monedaSaas }} {{ number_format($s->monto,2) }}</td>
                        <td>{{ optional($s->inicia_en)->format('d/m/Y') }}</td>
                        <td>{{ optional($s->termina_en)->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;color:#9aa3ab;padding:24px">Sin suscripciones.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div style="margin-top:14px">{{ $suscripciones->links() }}</div>
    </div>
@endsection
