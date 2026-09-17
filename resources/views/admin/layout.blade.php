<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Admin · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .app .sidebar.admin{background:#1f2937}
        .app .sidebar.admin .sidebar-title{color:#c7d2fe}
        .admin-tag{background:#6d28d9;color:#fff;font-size:10px;font-weight:800;padding:2px 8px;border-radius:10px;letter-spacing:.5px}
        .stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:20px}
        .stat{background:#fff;border:1px solid #e6eaef;border-radius:12px;padding:16px 18px}
        .stat .n{font-size:26px;font-weight:800;color:#2c3e50}
        .stat .l{font-size:12px;color:#8a94a0;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
        .st{padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700}
        .st.activa{background:#e3f7ec;color:#1e9e5a}
        .st.trial{background:#eaf4ff;color:#1560a8}
        .st.suspendida{background:#fdecec;color:#c0392b}
        .st.vencida,.st.cancelada{background:#f2f4f6;color:#7a8593}
    </style>
    @stack('styles')
</head>
<body>
<div class="app">
    <aside class="sidebar admin">
        <div class="sidebar-brand">
            <div class="logo" style="background:#6d28d9"><i class="fa-solid fa-shield-halved"></i></div>
        </div>
        <div class="sidebar-title"><i class="fa-solid fa-shield-halved"></i> <span>Super Admin</span></div>

        <ul class="sidebar-menu">
            <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-line"></i> <span class="txt">Resumen</span></a></li>

            <li class="group-label">Plataforma</li>
            <li><a href="{{ route('admin.tenants.index') }}" class="{{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}">
                <i class="fa-solid fa-building"></i> <span class="txt">Tenants</span></a></li>
            <li><a href="{{ route('admin.planes.index') }}" class="{{ request()->routeIs('admin.planes.*') ? 'active' : '' }}">
                <i class="fa-solid fa-layer-group"></i> <span class="txt">Planes</span></a></li>
            <li><a href="{{ route('admin.suscripciones.index') }}" class="{{ request()->routeIs('admin.suscripciones.*') ? 'active' : '' }}">
                <i class="fa-solid fa-file-invoice-dollar"></i> <span class="txt">Suscripciones</span></a></li>
            <li><a href="{{ route('admin.reportes.index') }}" class="{{ request()->routeIs('admin.reportes.*') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie"></i> <span class="txt">Reportes</span></a></li>

            <li class="group-label">Sistema</li>
            <li><a href="{{ route('admin.admins.index') }}" class="{{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-shield"></i> <span class="txt">Administradores</span></a></li>
            <li><a href="{{ route('admin.actividad.index') }}" class="{{ request()->routeIs('admin.actividad.*') ? 'active' : '' }}">
                <i class="fa-solid fa-clock-rotate-left"></i> <span class="txt">Actividad</span></a></li>
            <li><a href="{{ route('admin.configuracion.edit') }}" class="{{ request()->routeIs('admin.configuracion.*') ? 'active' : '' }}">
                <i class="fa-solid fa-gear"></i> <span class="txt">Configuración</span></a></li>
        </ul>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="toggle" onclick="document.querySelector('.sidebar').classList.toggle('collapsed')">
                <i class="fa-solid fa-bars"></i>
            </button>
            <span class="admin-tag">PANEL DE PLATAFORMA</span>
            <div class="spacer"></div>
            <div class="top-actions">
                <div class="user">
                    <div class="avatar" style="background:#6d28d9">{{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}</div>
                    <span style="font-size:13px">{{ auth()->user()->name }}</span>
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
            @include('layouts.flash')
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
