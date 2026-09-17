@extends('admin.layout')

@section('title', $admin->exists ? 'Editar administrador' : 'Nuevo administrador')

@section('content')
    <div class="page-head">
        <h1>{{ $admin->exists ? 'EDITAR ADMINISTRADOR' : 'NUEVO ADMINISTRADOR' }}</h1>
        <div class="breadcrumb">
            <a href="{{ route('admin.admins.index') }}">Administradores</a>
            <span class="sep">/</span> {{ $admin->exists ? $admin->name : 'Nuevo' }}
        </div>
    </div>

    @if ($errors->any())
        <div class="flash error"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
    @endif

    <div class="form-card" style="max-width:640px">
        <form method="POST" action="{{ $admin->exists ? route('admin.admins.update', $admin) : route('admin.admins.store') }}">
            @csrf
            @if($admin->exists) @method('PUT') @endif
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nombre *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $admin->name) }}" required>
                </div>
                <div class="form-group full">
                    <label>Correo *</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $admin->email) }}" required>
                </div>
                <div class="form-group">
                    <label>Contraseña {{ $admin->exists ? '(dejar en blanco para no cambiar)' : '*' }}</label>
                    <input type="password" name="password" class="form-control" {{ $admin->exists ? '' : 'required' }}>
                </div>
                <div class="form-group">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
                @if(! $admin->exists || $admin->id !== auth()->id())
                    <div class="form-group full">
                        <label><input type="checkbox" name="activo" value="1" {{ old('activo', $admin->activo ?? true) ? 'checked' : '' }}> Activo</label>
                    </div>
                @endif
            </div>
            <button class="btn btn-primary" style="width:auto"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
            <a href="{{ route('admin.admins.index') }}" class="btn btn-light" style="width:auto">Cancelar</a>
        </form>
    </div>
@endsection
