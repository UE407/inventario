@extends('admin.layout')

@section('title', 'Administradores')

@section('content')
    <div class="page-head">
        <h1>ADMINISTRADORES</h1>
        <div class="breadcrumb">Super Admin <span class="sep">/</span> Administradores</div>
    </div>

    <div style="margin-bottom:14px">
        <a href="{{ route('admin.admins.create') }}" class="btn btn-primary" style="width:auto">
            <i class="fa-solid fa-user-plus"></i> Nuevo administrador
        </a>
    </div>

    <div class="panel">
        <table class="table">
            <thead>
                <tr><th>Nombre</th><th>Correo</th><th>Estado</th><th style="text-align:right">Acciones</th></tr>
            </thead>
            <tbody>
                @foreach($admins as $a)
                    <tr>
                        <td><strong>{{ $a->name }}</strong> @if($a->id === auth()->id())<span class="badge-soft">tú</span>@endif</td>
                        <td>{{ $a->email }}</td>
                        <td>{!! $a->activo ? '<span class="st activa">activo</span>' : '<span class="st suspendida">inactivo</span>' !!}</td>
                        <td style="text-align:right;white-space:nowrap">
                            <a href="{{ route('admin.admins.edit', $a) }}" class="btn-icon edit"><i class="fa-solid fa-pen"></i></a>
                            @if($a->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.admins.destroy', $a) }}" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar a {{ $a->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-icon del"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
