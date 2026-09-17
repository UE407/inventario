<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-head">
                @if(($empresa->logo ?? false))
                    <div class="logo" style="background:#fff;overflow:hidden"><img src="{{ asset('storage/'.$empresa->logo) }}" style="width:100%;height:100%;object-fit:cover" alt=""></div>
                @else
                    <div class="logo"><i class="fa-solid fa-cube"></i></div>
                @endif
                <h1>{{ $empresa->nombre ?? config('app.name') }}</h1>
                <p>Sistema de Ventas e Inventario</p>
            </div>
            <div class="auth-body">
                @if ($errors->any())
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label>Correo electrónico</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email') }}" placeholder="tucorreo@empresa.com"
                               autofocus required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="••••••••" required>
                    </div>
                    <div class="checkbox-row">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember" style="margin:0">Mantener sesión iniciada</label>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-right-to-bracket"></i> Ingresar
                    </button>
                </form>

                <div class="auth-foot">
                    ¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
