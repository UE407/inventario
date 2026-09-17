@extends('admin.layout')

@section('title', $plan->exists ? 'Editar plan' : 'Nuevo plan')

@section('content')
    <div class="page-head">
        <h1>{{ $plan->exists ? 'EDITAR PLAN' : 'NUEVO PLAN' }}</h1>
        <div class="breadcrumb">
            <a href="{{ route('admin.planes.index') }}">Planes</a>
            <span class="sep">/</span> {{ $plan->exists ? $plan->nombre : 'Nuevo' }}
        </div>
    </div>

    @if ($errors->any())
        <div class="flash error"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
    @endif

    <div class="form-card" style="max-width:720px">
        <form method="POST" action="{{ $plan->exists ? route('admin.planes.update', $plan) : route('admin.planes.store') }}">
            @csrf
            @if($plan->exists) @method('PUT') @endif
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $plan->nombre) }}" required>
                </div>
                <div class="form-group">
                    <label>Slug *</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $plan->slug) }}" required>
                </div>
@php
    $monedaSaas = \App\Models\PlataformaConfig::actual()->moneda ?? 'Q';
@endphp

                <div class="form-group">
                    <label>Precio mensual ({{ $monedaSaas }}) *</label>
                    <input type="number" step="0.01" min="0" name="precio" class="form-control" value="{{ old('precio', $plan->precio ?? 0) }}" required>
                </div>
                <div class="form-group">
                    <label>Orden *</label>
                    <input type="number" min="0" name="orden" class="form-control" value="{{ old('orden', $plan->orden ?? 0) }}" required>
                </div>
                <div class="form-group">
                    <label>Límite de productos (vacío = ∞)</label>
                    <input type="number" min="0" name="limite_productos" class="form-control" value="{{ old('limite_productos', $plan->limite_productos) }}">
                </div>
                <div class="form-group">
                    <label>Límite de usuarios (vacío = ∞)</label>
                    <input type="number" min="0" name="limite_usuarios" class="form-control" value="{{ old('limite_usuarios', $plan->limite_usuarios) }}">
                </div>
                <div class="form-group">
                    <label>Límite de ventas/mes (vacío = ∞)</label>
                    <input type="number" min="0" name="limite_ventas_mes" class="form-control" value="{{ old('limite_ventas_mes', $plan->limite_ventas_mes) }}">
                </div>
                <div class="form-group full">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion', $plan->descripcion) }}">
                </div>
                <div class="form-group full">
                    <label><input type="checkbox" name="activo" value="1" {{ old('activo', $plan->activo ?? true) ? 'checked' : '' }}> Plan activo (visible para los tenants)</label>
                </div>
            </div>
            <button class="btn btn-primary" style="width:auto"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
            <a href="{{ route('admin.planes.index') }}" class="btn btn-light" style="width:auto">Cancelar</a>
        </form>
    </div>
@endsection
