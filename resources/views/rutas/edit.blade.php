@extends('layouts.app')

@section('title', 'Editar Ruta de Entrega')

@section('content')
    <div class="page-head" style="margin-bottom:20px;">
        <h1><i class="fa-solid fa-route"></i> Editar Ruta de Entrega</h1>
        <div style="font-size:0.85rem; color:#64748b; margin-top:2px;">
            Actualiza los parámetros de la ruta {{ $ruta->nombre }}.
        </div>
    </div>

    <div class="form-card" style="max-width:600px;">
        <form action="{{ route('rutas.update', $ruta) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="nombre">Nombre de la Ruta <span style="color:#e74c3c">*</span></label>
                <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $ruta->nombre) }}" required>
            </div>

            <div class="form-group">
                <label for="codigo">Código de Ruta (Opcional)</label>
                <input type="text" name="codigo" id="codigo" class="form-control" value="{{ old('codigo', $ruta->codigo) }}">
            </div>

            <div class="form-group">
                <label for="dias_entrega_estimados">Días Estimados de Entrega <span style="color:#e74c3c">*</span></label>
                <input type="number" name="dias_entrega_estimados" id="dias_entrega_estimados" class="form-control" value="{{ old('dias_entrega_estimados', $ruta->dias_entrega_estimados) }}" min="0" max="30" required>
                <small style="color:#64748b;">Número de días para auto-calcular la fecha estimada de entrega.</small>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción / Cobertura (Opcional)</label>
                <textarea name="descripcion" id="descripcion" class="form-control" rows="3">{{ old('descripcion', $ruta->descripcion) }}</textarea>
            </div>

            <div class="checkbox-row" style="margin-top:15px; margin-bottom:20px;">
                <input type="checkbox" name="activo" id="activo" value="1" {{ old('activo', $ruta->activo) ? 'checked' : '' }}>
                <label for="activo" style="margin:0; font-weight:600;">Ruta Activa</label>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Guardar Cambios</button>
                <a href="{{ route('rutas.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
