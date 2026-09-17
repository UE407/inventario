<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-head">
                <div class="logo"><i class="fa-solid fa-cube"></i></div>
                <h1>Crear cuenta</h1>
                <p>Configura tu negocio en minutos</p>
            </div>
            <div class="auth-body">
                @if ($errors->any())
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="form-group">
                        <label>Nombre del negocio</label>
                        <input type="text" name="empresa" class="form-control"
                               value="{{ old('empresa') }}" placeholder="Ej. Bodega Don Pepe" autofocus required>
                    </div>
                    <div class="form-group">
                        <label>Nombre completo</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name') }}" placeholder="Tu nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email') }}" placeholder="tucorreo@empresa.com" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Repite la contraseña" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Crear cuenta
                    </button>
                </form>

                <div class="auth-foot">
                    ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
