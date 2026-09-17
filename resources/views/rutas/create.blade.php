@extends('layouts.app')

@section('title', 'Nueva Ruta de Entrega')

@section('content')
    <div class="page-head" style="margin-bottom:20px;">
        <h1><i class="fa-solid fa-route"></i> Nueva Ruta de Entrega</h1>
        <div style="font-size:0.85rem; color:#64748b; margin-top:2px;">
            Define una nueva ruta de distribución y su tiempo estimado de entrega.
        </div>
    </div>

    <div class="form-card" style="max-width:600px;">
        <form action="{{ route('rutas.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="nombre">Nombre de la Ruta <span style="color:#e74c3c">*</span></label>
                <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre') }}" placeholder="Ej. Ruta 1 - Carretera a El Salvador" required>
            </div>

            <div class="form-group">
                <label for="codigo">Código de Ruta (Opcional)</label>
                <input type="text" name="codigo" id="codigo" class="form-control" value="{{ old('codigo') }}" placeholder="Ej. RUT-01">
            </div>

            <div class="form-group">
                <label for="dias_entrega_estimados">Días Estimados de Entrega <span style="color:#e74c3c">*</span></label>
                <input type="number" name="dias_entrega_estimados" id="dias_entrega_estimados" class="form-control" value="{{ old('dias_entrega_estimados', 1) }}" min="0" max="30" required>
                <small style="color:#64748b;">Número de días hábiles/posteriores a la toma del pedido para realizar la entrega.</small>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción / Cobertura (Opcional)</label>
                <textarea name="descripcion" id="descripcion" class="form-control" rows="3" placeholder="Ej. Cobertura en municipios de Fraijanes, Santa Catarina Pinula y San José Pinula">{{ old('descripcion') }}</textarea>
            </div>

            <div class="checkbox-row" style="margin-top:15px; margin-bottom:20px;">
                <input type="checkbox" name="activo" id="activo" value="1" {{ old('activo', 1) ? 'checked' : '' }}>
                <label for="activo" style="margin:0; font-weight:600;">Ruta Activa</label>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Guardar Ruta</button>
                <a href="{{ route('rutas.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
