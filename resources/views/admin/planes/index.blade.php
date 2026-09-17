@extends('admin.layout')

@section('title', 'Planes')

@php
    $monedaSaas = \App\Models\PlataformaConfig::actual()->moneda ?? 'Q';
@endphp

@section('content')
    <div class="page-head">
        <h1>PLANES</h1>
        <div class="breadcrumb">Super Admin <span class="sep">/</span> Planes</div>
    </div>

    <div style="margin-bottom:14px">
        <a href="{{ route('admin.planes.create') }}" class="btn btn-primary" style="width:auto">
            <i class="fa-solid fa-plus"></i> Nuevo plan
        </a>
    </div>

    <div class="form-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Plan</th><th>Slug</th><th style="text-align:right">Precio</th>
                    <th style="text-align:center">Productos</th><th style="text-align:center">Usuarios</th>
                    <th style="text-align:center">Ventas/mes</th><th style="text-align:center">Tenants</th>
                    <th style="text-align:center">Activo</th><th style="text-align:right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($planes as $p)
                    <tr>
                        <td><strong>{{ $p->nombre }}</strong></td>
                        <td>{{ $p->slug }}</td>
                        <td style="text-align:right">{{ $monedaSaas }} {{ number_format($p->precio,2) }}</td>
                        <td style="text-align:center">{{ $p->limite_productos ?? '∞' }}</td>
                        <td style="text-align:center">{{ $p->limite_usuarios ?? '∞' }}</td>
                        <td style="text-align:center">{{ $p->limite_ventas_mes ?? '∞' }}</td>
                        <td style="text-align:center">{{ $p->empresas_count }}</td>
                        <td style="text-align:center">{!! $p->activo ? '<span class="st activa">sí</span>' : '<span class="st suspendida">no</span>' !!}</td>
                        <td style="text-align:right;white-space:nowrap">
                            <a href="{{ route('admin.planes.edit', $p) }}" class="btn-icon edit"><i class="fa-solid fa-pen"></i></a>
                            <form method="POST" action="{{ route('admin.planes.destroy', $p) }}" style="display:inline"
                                  onsubmit="return confirm('¿Eliminar este plan?')">
                                @csrf @method('DELETE')
                                <button class="btn-icon del"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
