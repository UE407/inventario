@extends('layouts.app')

@section('title', 'Suscripción')

@section('content')
    <div class="page-head">
        <h1>SUSCRIPCIÓN</h1>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <span class="sep">/</span> Configuración <span class="sep">/</span> Suscripción
        </div>
    </div>

    @php $esAdmin = auth()->user()->rol === 'admin'; @endphp

    {{-- Estado actual --}}
    <div class="form-card" style="max-width:980px;margin-bottom:18px">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px;align-items:center">
            <div>
                <div style="font-size:12px;color:#8a94a0;font-weight:700;letter-spacing:.5px">PLAN ACTUAL</div>
                <div style="font-size:22px;font-weight:800;color:#2c3e50">
                    {{ $empresa->plan->nombre ?? 'Sin plan' }}
                </div>
                @if($empresa->enTrial())
                    <span class="badge-soft" style="background:#eaf4ff;color:#1560a8">
                        Prueba · faltan {{ $empresa->diasTrialRestantes() }} día(s)
                    </span>
                @elseif($empresa->estado_suscripcion === 'activa')
                    <span class="badge-soft" style="background:#e3f7ec;color:#1e9e5a">
                        Activa
                        @if($empresa->suscripcion_termina_en)
                            · renueva el {{ $empresa->suscripcion_termina_en->format('d/m/Y') }}
                        @endif
                    </span>
                @else
                    <span class="badge-soft" style="background:#fdecec;color:#c0392b">
                        {{ ucfirst($empresa->estado_suscripcion) }}
                    </span>
                @endif
            </div>
            <div style="text-align:right">
                <div style="font-size:12px;color:#8a94a0;font-weight:700">PRECIO</div>
                <div style="font-size:20px;font-weight:800;color:#2c3e50">
                    {{ $empresa->moneda }} {{ number_format($empresa->plan->precio ?? 0, 2) }}<span style="font-size:12px;color:#8a94a0">/mes</span>
                </div>
            </div>
        </div>

        {{-- Uso vs límites --}}
        <div class="grid-2" style="margin-top:18px">
            @php
                $recursos = [
                    'productos' => ['Productos', $uso['productos']],
                    'usuarios'  => ['Usuarios',  $uso['usuarios']],
                    'ventas_mes'=> ['Ventas del mes', $uso['ventas_mes']],
                ];
            @endphp
            @foreach($recursos as $key => [$label, $actual])
                @php
                    $limite = $empresa->limite($key);
                    $pct = $limite ? min(100, round($actual / max($limite,1) * 100)) : 0;
                    $lleno = $limite !== null && $actual >= $limite;
                @endphp
                <div style="background:#f7f9fb;border-radius:8px;padding:12px 14px">
                    <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;color:#4a5560">
                        <span>{{ $label }}</span>
                        <span>{{ $actual }} / {{ $limite === null ? '∞' : $limite }}</span>
                    </div>
                    <div style="height:7px;background:#e6eaef;border-radius:5px;margin-top:7px;overflow:hidden">
                        <div style="height:100%;width:{{ $limite === null ? 6 : $pct }}%;background:{{ $lleno ? '#e74c3c' : 'var(--brand)' }}"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Planes disponibles --}}
    <div style="font-size:13px;color:#8a94a0;font-weight:700;letter-spacing:.5px;margin-bottom:10px">PLANES DISPONIBLES</div>
    <div class="planes-grid">
        @foreach($planes as $plan)
            @php $actualPlan = $empresa->plan_id === $plan->id; @endphp
            <div class="plan-card {{ $actualPlan ? 'is-current' : '' }}">
                <div class="plan-name">{{ $plan->nombre }}</div>
                <div class="plan-price">
                    {{ $empresa->moneda }} {{ number_format($plan->precio, 2) }}
                    <span>/mes</span>
                </div>
                @if($plan->descripcion)
                    <div class="plan-desc">{{ $plan->descripcion }}</div>
                @endif
                <ul class="plan-features">
                    <li><i class="fa-solid fa-box"></i> {{ $plan->limite_productos === null ? 'Productos ilimitados' : $plan->limite_productos . ' productos' }}</li>
                    <li><i class="fa-solid fa-users"></i> {{ $plan->limite_usuarios === null ? 'Usuarios ilimitados' : $plan->limite_usuarios . ' usuarios' }}</li>
                    <li><i class="fa-solid fa-receipt"></i> {{ $plan->limite_ventas_mes === null ? 'Ventas ilimitadas' : $plan->limite_ventas_mes . ' ventas/mes' }}</li>
                </ul>
                @if($actualPlan)
                    <button class="btn btn-light" disabled style="width:100%">Plan actual</button>
                @elseif($esAdmin)
                    <form method="POST" action="{{ route('suscripcion.cambiar') }}"
                          onsubmit="return confirm('¿Cambiar al plan {{ $plan->nombre }}?')">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="btn btn-primary" style="width:100%">
                            Elegir {{ $plan->nombre }}
                        </button>
                    </form>
                @else
                    <button class="btn btn-light" disabled style="width:100%">Solo el administrador</button>
                @endif
            </div>
        @endforeach
    </div>

    <p style="font-size:12px;color:#9aa4af;margin-top:16px">
        <i class="fa-solid fa-circle-info"></i>
        La activación es manual por ahora. La integración con la pasarela de pago (Stripe / MercadoPago / Culqi) es el siguiente paso.
    </p>

    <style>
        .planes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px}
        .plan-card{background:#fff;border:1px solid #e6eaef;border-radius:12px;padding:20px 18px;display:flex;flex-direction:column}
        .plan-card.is-current{border-color:var(--brand);box-shadow:0 0 0 2px rgba(41,98,255,.12)}
        .plan-name{font-size:16px;font-weight:800;color:#2c3e50}
        .plan-price{font-size:24px;font-weight:800;color:var(--brand);margin:6px 0}
        .plan-price span{font-size:12px;color:#8a94a0;font-weight:600}
        .plan-desc{font-size:12px;color:#7a8593;margin-bottom:10px}
        .plan-features{list-style:none;padding:0;margin:8px 0 16px;font-size:13px;color:#4a5560}
        .plan-features li{padding:5px 0;border-bottom:1px dashed #eef1f4}
        .plan-features li i{color:var(--brand);width:16px;margin-right:6px}
    </style>
@endsection
