@extends('admin.layout')

@section('title', 'Configuración')

@section('content')
    <div class="page-head">
        <h1>CONFIGURACIÓN DE LA PLATAFORMA</h1>
        <div class="breadcrumb">Super Admin <span class="sep">/</span> Configuración</div>
    </div>

    @if ($errors->any())
        <div class="flash error"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
    @endif

    <div class="form-card" style="max-width:760px">
        <form method="POST" action="{{ route('admin.configuracion.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nombre del SaaS *</label>
                    <input type="text" name="nombre_saas" class="form-control" value="{{ old('nombre_saas', $config->nombre_saas) }}" required>
                </div>
                <div class="form-group">
                    <label>Días de prueba por defecto *</label>
                    <input type="number" name="dias_trial" min="0" max="365" class="form-control" value="{{ old('dias_trial', $config->dias_trial) }}" required>
                    <div class="help">Se aplica a cada negocio que se registra.</div>
                </div>
                <div class="form-group">
                    <label>Símbolo de moneda *</label>
                    <input type="text" name="moneda" maxlength="5" class="form-control" value="{{ old('moneda', $config->moneda) }}" required>
                </div>
                <div class="form-group">
                    <label>Correo de soporte</label>
                    <input type="email" name="correo_soporte" class="form-control" value="{{ old('correo_soporte', $config->correo_soporte) }}">
                </div>
                <div class="form-group">
                    <label>Logo de la plataforma</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    @if($config->logo)
                        <div class="help"><img src="{{ asset('storage/'.$config->logo) }}" style="height:60px;border-radius:8px;margin-top:6px"></div>
                    @endif
                </div>
                <div class="form-group full">
                    <label>Mensaje de bienvenida</label>
                    <input type="text" name="mensaje_bienvenida" class="form-control" value="{{ old('mensaje_bienvenida', $config->mensaje_bienvenida) }}"
                           placeholder="Se puede mostrar a los negocios al ingresar.">
                </div>
            </div>
            <button class="btn btn-primary" style="width:auto"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
        </form>
    </div>
@endsection
