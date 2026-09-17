<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
<div class="app">

    {{-- ============ SIDEBAR ============ --}}
    <aside class="sidebar">
        <div class="sidebar-brand">
            @if(($empresa->logo ?? false))
                <div class="logo" style="background:#fff;overflow:hidden"><img src="{{ asset('storage/'.$empresa->logo) }}" style="width:100%;height:100%;object-fit:cover" alt=""></div>
            @else
                <div class="logo"><i class="fa-solid fa-cube"></i></div>
            @endif
        </div>
        <div class="sidebar-title"><i class="fa-solid fa-gauge-high"></i> <span>{{ $empresa->nombre ?? config('app.name') }}</span></div>

        <ul class="sidebar-menu">
            <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-desktop"></i> <span class="txt">Dashboard</span></a></li>

            <li class="group-label">Ventas</li>
            <li><a href="{{ route('ventas.pos') }}" class="{{ request()->routeIs('ventas.pos') ? 'active' : '' }}">
                <i class="fa-solid fa-cash-register"></i> <span class="txt">Punto de Venta</span></a></li>
            <li><a href="{{ route('ventas.index') }}" class="{{ request()->routeIs('ventas.index') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-line"></i> <span class="txt">Ventas</span></a></li>
            <li><a href="{{ route('pedidos.index') }}" class="{{ request()->routeIs('pedidos.*') ? 'active' : '' }}">
                <i class="fa-solid fa-truck-fast"></i> <span class="txt">Pedidos de Ruta</span></a></li>
            <li><a href="{{ route('rutas.index') }}" class="{{ request()->routeIs('rutas.*') ? 'active' : '' }}">
                <i class="fa-solid fa-route"></i> <span class="txt">Rutas de Entrega</span></a></li>
            <li><a href="{{ route('comprobantes.index') }}" class="{{ request()->routeIs('comprobantes.*') ? 'active' : '' }}">
                <i class="fa-solid fa-receipt"></i> <span class="txt">Comprobantes</span></a></li>

            <li class="group-label">Inventario</li>
            <li><a href="{{ route('productos.index') }}" class="{{ request()->routeIs('productos.*') ? 'active' : '' }}">
                <i class="fa-solid fa-box"></i> <span class="txt">Productos</span></a></li>
            <li><a href="{{ route('lotes.index') }}" class="{{ request()->routeIs('lotes.*') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-xmark"></i> <span class="txt">Lotes y Vencimientos</span></a></li>
            <li><a href="{{ route('categorias.index') }}" class="{{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                <i class="fa-solid fa-tags"></i> <span class="txt">Categorías</span></a></li>
            <li><a href="{{ route('marcas.index') }}" class="{{ request()->routeIs('marcas.*') ? 'active' : '' }}">
                <i class="fa-solid fa-trademark"></i> <span class="txt">Marcas</span></a></li>
            <li><a href="{{ route('inventario.kardex') }}" class="{{ request()->routeIs('inventario.kardex') ? 'active' : '' }}">
                <i class="fa-solid fa-arrow-right-arrow-left"></i> <span class="txt">Kardex</span></a></li>
            <li><a href="{{ route('inventario.ajustes') }}" class="{{ request()->routeIs('inventario.ajustes') ? 'active' : '' }}">
                <i class="fa-solid fa-sliders"></i> <span class="txt">Ajustes de Stock</span></a></li>

            <li class="group-label">Compras</li>
            <li><a href="{{ route('compras.index') }}" class="{{ request()->routeIs('compras.*') ? 'active' : '' }}">
                <i class="fa-solid fa-truck-ramp-box"></i> <span class="txt">Compras</span></a></li>
            <li><a href="{{ route('proveedores.index') }}" class="{{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
                <i class="fa-solid fa-industry"></i> <span class="txt">Proveedores</span></a></li>

            <li class="group-label">Personas</li>
            <li><a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i> <span class="txt">Clientes</span></a></li>

            <li class="group-label">Reportes</li>
            <li><a href="{{ route('reportes.ventas') }}" class="{{ request()->routeIs('reportes.ventas') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-column"></i> <span class="txt">Ventas</span></a></li>
            <li><a href="{{ route('reportes.inventario') }}" class="{{ request()->routeIs('reportes.inventario') ? 'active' : '' }}">
                <i class="fa-solid fa-warehouse"></i> <span class="txt">Inventario</span></a></li>
            <li><a href="{{ route('reportes.ganancias') }}" class="{{ request()->routeIs('reportes.ganancias') ? 'active' : '' }}">
                <i class="fa-solid fa-coins"></i> <span class="txt">Ganancias</span></a></li>

            <li class="group-label">Configuración</li>
            @if(auth()->user()->rol === 'admin')
            <li><a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-gear"></i> <span class="txt">Usuarios</span></a></li>
            @endif
            <li><a href="{{ route('configuracion.empresa') }}" class="{{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                <i class="fa-solid fa-building"></i> <span class="txt">Empresa</span></a></li>
            @if(auth()->user()->rol === 'admin')
            <li><a href="{{ route('facturacion.config') }}" class="{{ request()->routeIs('facturacion.*') ? 'active' : '' }}">
                <i class="fa-solid fa-file-invoice-dollar"></i> <span class="txt">Facturación Electrónica</span></a></li>
            @endif
            <li><a href="{{ route('suscripcion.index') }}" class="{{ request()->routeIs('suscripcion.*') ? 'active' : '' }}">
                <i class="fa-solid fa-crown"></i> <span class="txt">Suscripción</span></a></li>
        </ul>
    </aside>

    {{-- ============ MAIN ============ --}}
    <div class="main">
        <header class="topbar">
            <button class="toggle" onclick="document.querySelector('.sidebar').classList.toggle('collapsed')">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="spacer"></div>
            <div class="top-actions">
                <span class="ico"><i class="fa-regular fa-envelope"></i><span class="badge">3</span></span>
                <span class="ico"><i class="fa-regular fa-bell"></i><span class="badge">5</span></span>
                <div class="user">
                    <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                    <span style="font-size:13px">{{ auth()->user()->name ?? 'Usuario' }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-btn" title="Cerrar sesión">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="content">
            @if(session('impersonator_id'))
                <div class="sub-banner vencida" style="background:#ede9fe;color:#5b21b6;border-color:#c4b5fd">
                    <i class="fa-solid fa-user-secret"></i>
                    Estás viendo el sistema como este tenant (modo soporte).
                    <form method="POST" action="{{ route('impersonar.salir') }}" style="margin-left:auto">
                        @csrf
                        <button type="submit" style="background:none;border:none;color:#5b21b6;font-weight:700;text-decoration:underline;cursor:pointer">
                            Volver al panel
                        </button>
                    </form>
                </div>
            @endif
            @isset($empresa)
                @if($empresa->enTrial())
                    <div class="sub-banner trial">
                        <i class="fa-solid fa-hourglass-half"></i>
                        Estás en tu prueba gratis: quedan {{ $empresa->diasTrialRestantes() }} día(s).
                        <a href="{{ route('suscripcion.index') }}">Ver planes</a>
                    </div>
                @elseif(isset($empresa->estado_suscripcion) && ! $empresa->suscripcionVigente())
                    <div class="sub-banner vencida">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Tu suscripción no está activa.
                        <a href="{{ route('suscripcion.index') }}">Elegir un plan</a>
                    </div>
                @endif
            @endisset
            @include('layouts.flash')
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="{{ asset('js/searchable-select.js') }}"></script>
@stack('scripts')
</body>
</html>
