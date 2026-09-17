@extends('layouts.app')

@section('title', 'Detalle de Pedido '.$pedido->numero)

@php
    $moneda = $empresa->moneda ?? 'Q';
@endphp

@section('content')
    <div class="page-head">
        <h1>PEDIDO {{ $pedido->numero }}</h1>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <span class="sep">/</span> <a href="{{ route('pedidos.index') }}">Pedidos</a>
            <span class="sep">/</span> {{ $pedido->numero }}
        </div>
    </div>

    @if (session('success'))
        <div class="flash success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash error"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <div class="grid-2" style="grid-template-columns: 2fr 1fr; gap:20px;">
        <div class="card" style="padding:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;border-bottom:1px solid #e2e8f0;padding-bottom:12px;">
                <div>
                    <h2 style="margin:0;font-size:1.3rem;">Información del Pedido</h2>
                    <span style="font-size:0.85rem;color:#64748b;">Tomado el {{ $pedido->fecha_pedido->format('d/m/Y H:i') }} por {{ $pedido->vendedor->name ?? 'Sistema' }}</span>
                </div>
                <div>
                    @if($pedido->estado === 'PENDIENTE')
                        <span class="badge" style="background:#fef3c7; color:#92400e; font-weight:700; font-size:0.9rem; padding:6px 12px;">
                            <i class="fa-solid fa-hourglass-half"></i> PENDIENTE (Stock Reservado)
                        </span>
                    @elseif($pedido->estado === 'CONVERTIDO')
                        <span class="badge" style="background:#d1fae5; color:#065f46; font-weight:700; font-size:0.9rem; padding:6px 12px;">
                            <i class="fa-solid fa-circle-check"></i> CONVERTIDO A VENTA
                        </span>
                    @else
                        <span class="badge" style="background:#fee2e2; color:#991b1b; font-weight:700; font-size:0.9rem; padding:6px 12px;">
                            <i class="fa-solid fa-ban"></i> CANCELADO (Stock Liberado)
                        </span>
                    @endif
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:15px;margin-bottom:20px;background:#f8fafc;padding:15px;border-radius:8px;">
                <div>
                    <strong style="display:block;font-size:0.8rem;color:#64748b;">CLIENTE</strong>
                    <span style="font-weight:600;color:#1e293b;">{{ $pedido->cliente->nombre ?? 'Consumidor Final' }}</span>
                    @if($pedido->cliente && $pedido->cliente->numero_documento)
                        <div style="font-size:0.8rem;color:#64748b;">{{ $pedido->cliente->tipo_documento }}: {{ $pedido->cliente->numero_documento }}</div>
                    @endif
                </div>
                <div>
                    <strong style="display:block;font-size:0.8rem;color:#64748b;">RUTA DE ENTREGA</strong>
                    <span style="font-weight:600;color:#0369a1;">
                        <i class="fa-solid fa-route"></i> {{ $pedido->ruta->nombre ?? $pedido->cliente?->ruta?->nombre ?? '— Sin Ruta —' }}
                    </span>
                </div>
                <div>
                    <strong style="display:block;font-size:0.8rem;color:#64748b;">FECHA TOMA DE PEDIDO</strong>
                    <span style="font-weight:600;color:#1e293b;">{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y H:i') : '-' }}</span>
                </div>
                <div>
                    <strong style="display:block;font-size:0.8rem;color:#64748b;">ENTREGA ESTIMADA</strong>
                    <span style="font-weight:600;color:#1e293b;">{{ $pedido->fecha_entrega_estimada ? $pedido->fecha_entrega_estimada->format('d/m/Y') : 'Inmediata' }}</span>
                </div>
                <div>
                    <strong style="display:block;font-size:0.8rem;color:#64748b;">OBSERVACIÓN / NOTA</strong>
                    <span style="color:#334155;">{{ $pedido->observacion ?? 'Ninguna' }}</span>
                </div>
            </div>

            <h3>Ítems del Pedido</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Lote Reservado</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedido->detalles as $det)
                            <tr>
                                <td>
                                    <div style="font-weight:600;">{{ $det->producto->nombre ?? 'Producto' }}</div>
                                    <small class="text-muted">Cód: {{ $det->producto->codigo ?? '-' }}</small>
                                </td>
                                <td>
                                    @if($det->lote)
                                        <span class="badge" style="background:#e0f2fe;color:#0369a1;">
                                            {{ $det->lote->codigo_lote }} (Vence: {{ $det->lote->fecha_vencimiento->format('d/m/Y') }})
                                        </span>
                                    @else
                                        <span class="badge" style="background:#f1f5f9;color:#64748b;">General</span>
                                    @endif
                                </td>
                                <td><strong>{{ $det->cantidad }}</strong> {{ $det->producto->unidad ?? 'UND' }}</td>
                                <td>{{ $moneda }} {{ number_format($det->precio, 2) }}</td>
                                <td><strong>{{ $moneda }} {{ number_format($det->subtotal, 2) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:15px;">
                <div class="totales" style="min-width:240px">
                    <div class="r"><span>Subtotal (con IVA)</span><span>{{ $moneda }} {{ number_format($pedido->subtotal, 2) }}</span></div>
                    @if($pedido->impuesto > 0)
                        <div class="r"><span>Base Gravable (sin IVA)</span><span>{{ $moneda }} {{ number_format($pedido->total - $pedido->impuesto, 2) }}</span></div>
                        <div class="r"><span>IVA Incluido (12%)</span><span>{{ $moneda }} {{ number_format($pedido->impuesto, 2) }}</span></div>
                    @endif
                    <div class="r total"><span>Total Pedido</span><span>{{ $moneda }} {{ number_format($pedido->total, 2) }}</span></div>
                </div>
            </div>
        </div>

        <div>
            @if($pedido->estado === 'PENDIENTE')
                <div class="card" style="padding:20px;border-top:4px solid #10b981;">
                    <h3 style="margin-top:0;color:#065f46;"><i class="fa-solid fa-truck-fast"></i> Despachar y Convertir a Venta</h3>
                    <p style="font-size:0.85rem;color:#475569;margin-bottom:15px;">
                        Al convertir este pedido se emitirá el comprobante para la ruta. <strong>El cobro y la entrega se realizarán en ruta al entregar el producto al cliente.</strong>
                    </p>

                    <form id="formConvertir" method="POST" action="{{ route('pedidos.convertir', $pedido) }}">
                        @csrf
                        <div class="form-group" style="margin-bottom:12px;">
                            <label>Comprobante <span style="color:#e74c3c">*</span></label>
                            <select name="tipo_comprobante" id="c_tipo_comprobante" class="form-control" required>
                                <option value="TICKET">Ticket</option>
                                <option value="BOLETA">Boleta</option>
                                <option value="FACTURA">Factura Electrónica (FEL SAT)</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom:12px;">
                            <label>Método de Pago <span style="color:#e74c3c">*</span></label>
                            <select name="metodo_pago" id="c_metodo_pago" class="form-control" onchange="toggleEfec()" required>
                                <option value="CONTRA_ENTREGA" selected>Pago Contra Entrega (En Ruta - Por Cobrar)</option>
                                <option value="CREDITO">Crédito / Pendiente de Pago</option>
                                <option value="EFECTIVO">Efectivo (Cobrado por Adelantado)</option>
                                <option value="TARJETA">Tarjeta de Débito/Crédito</option>
                                <option value="TRANSFERENCIA">Transferencia Bancaria</option>
                            </select>
                        </div>

                        <div class="form-group" id="wrapEfec" style="margin-bottom:12px;display:none;">
                            <label>Efectivo Recibido ({{ $moneda }})</label>
                            <input type="number" step="0.01" min="0" name="efectivo_recibido" id="c_efectivo" class="form-control" placeholder="0.00">
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label>Descuento Adicional ({{ $moneda }})</label>
                            <input type="number" step="0.01" min="0" name="descuento" value="0" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-success" style="width:100%;margin-bottom:10px;">
                            <i class="fa-solid fa-check"></i> Despachar y Convertir a Venta
                        </button>
                    </form>

                    <form method="POST" action="{{ route('pedidos.cancelar', $pedido) }}" onsubmit="return confirm('¿Cancelar este pedido y liberar el stock reservado?')">
                        @csrf
                        <button type="submit" class="btn btn-light" style="width:100%;color:#ef4444;border-color:#fca5a5;">
                            <i class="fa-solid fa-ban"></i> Cancelar Pedido (Liberar Stock)
                        </button>
                    </form>
                </div>
            @elseif($pedido->estado === 'CONVERTIDO' && $pedido->venta)
                <div class="card" style="padding:20px;border-top:4px solid #10b981;text-align:center;">
                    <i class="fa-solid fa-circle-check" style="font-size:3rem;color:#10b981;margin-bottom:10px;"></i>
                    <h3 style="margin:0 0 5px 0;">Pedido Convertido</h3>
                    <p style="font-size:0.9rem;color:#64748b;margin-bottom:15px;">Venta registrada con N° <strong>{{ $pedido->venta->numero }}</strong></p>

                    <a href="{{ route('ventas.show', $pedido->venta) }}" class="btn btn-primary" style="width:100%;">
                        <i class="fa-solid fa-receipt"></i> Ver Venta / Ticket FEL
                    </a>
                </div>
            @else
                <div class="card" style="padding:20px;border-top:4px solid #ef4444;text-align:center;">
                    <i class="fa-solid fa-ban" style="font-size:3rem;color:#ef4444;margin-bottom:10px;"></i>
                    <h3 style="margin:0 0 5px 0;">Pedido Cancelado</h3>
                    <p style="font-size:0.85rem;color:#64748b;">El stock reservado fue liberado al inventario.</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
function toggleEfec() {
    const m = document.getElementById('c_metodo_pago').value;
    document.getElementById('wrapEfec').style.display = (m === 'EFECTIVO') ? 'block' : 'none';
}
toggleEfec();
</script>
@endpush
