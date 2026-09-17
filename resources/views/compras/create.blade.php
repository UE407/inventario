@extends('layouts.app')

@section('title', 'Nueva compra')

@php
    $simMoneda = $empresa->moneda ?? 'Q';
@endphp

@section('content')
    <div class="page-head">
        <h1>NUEVA COMPRA</h1>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <span class="sep">/</span> <a href="{{ route('compras.index') }}">Compras</a>
            <span class="sep">/</span> Nueva
        </div>
    </div>

    @if ($errors->any())
        <div class="flash error"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('compras.store') }}" id="formCompra">
        @csrf
        <div class="form-card" style="max-width:100%">
            <div class="form-grid">
                <div class="form-group">
                    <label>Proveedor <span style="color:#e74c3c">*</span></label>
                    <select name="proveedor_id" id="proveedor_id" class="form-control" required>
                        <option value="">— Selecciona —</option>
                        @foreach($proveedores as $p)
                            <option value="{{ $p->id }}" {{ old('proveedor_id') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha <span style="color:#e74c3c">*</span></label>
                    <input type="date" name="fecha" class="form-control" value="{{ old('fecha', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="form-group full">
                    <label>Observación</label>
                    <input type="text" name="observacion" class="form-control" value="{{ old('observacion') }}" placeholder="Opcional (N° de factura, guía, etc.)">
                </div>
            </div>

            <h3 style="margin:22px 0 12px;color:#4a5560;font-size:14px"><span class="dot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--brand);margin-right:6px"></span> Productos a comprar</h3>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">
                <div style="flex:2;min-width:250px">
                    <select id="prodSelect" class="form-control" onchange="onProductoSelected()">
                        <option value="">Selecciona un producto para agregar...</option>
                        @foreach($productos as $prod)
                            <option value="{{ $prod->id }}"
                                data-nombre="{{ $prod->nombre }}"
                                data-codigo="{{ $prod->codigo }}"
                                data-precio="{{ $prod->precio_compra }}"
                                data-stock="{{ $prod->stock }}"
                                data-unidad="{{ $prod->unidad }}"
                                data-tieneempaque="{{ $prod->tiene_empaque ? '1' : '0' }}"
                                data-nombreempaque="{{ $prod->nombre_empaque ?? 'Fardo' }}"
                                data-cantempaque="{{ $prod->cant_por_empaque ?? 1 }}"
                                data-perecedero="{{ $prod->es_perecedero ? '1' : '0' }}"
                                data-tienevariantes="{{ $prod->tiene_variantes ? '1' : '0' }}"
                                data-variantes='@json($prod->variantes)'>
                                {{ $prod->codigo }} · {{ $prod->nombre }} {{ $prod->tiene_variantes ? '🎨 [Multi-sabor]' : '' }} {{ $prod->tiene_empaque ? '📦 ['.$prod->nombre_empaque.' x'.$prod->cant_por_empaque.']' : '' }} {{ $prod->es_perecedero ? '🥦 (Perecedero)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="wrapSaborSelect" style="flex:1.8;min-width:220px;display:none;align-items:center;gap:6px">
                    <select id="saborSelect" class="form-control" style="border-color:#ca8a04; background:#fefce8; font-weight:600;"></select>
                    <button type="button" class="btn btn-warning btn-sm" style="white-space:nowrap; padding:7px 12px; font-weight:600;" onclick="abrirModalVariantesBloque()" title="Ingresar cantidades para todos los sabores a la vez">
                        <i class="fa-solid fa-layer-group"></i> Bloque
                    </button>
                </div>

                <div id="wrapPresentacionSelect" style="flex:1.2;min-width:180px;display:none;">
                    <select id="presentacionSelect" class="form-control" style="border-color:#0284c7;">
                        <option value="UNIDAD">Unidad Mínima</option>
                    </select>
                </div>

                <button type="button" class="btn btn-primary btn-sm" style="width:auto" onclick="addLinea()">
                    <i class="fa-solid fa-plus"></i> Agregar
                </button>
            </div>

            <div class="panel-scroll">
            <table class="table" id="tablaItems">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th style="width:130px">Lote (Opcional)</th>
                        <th style="width:150px">Vencimiento</th>
                        <th style="width:100px">Cantidad</th>
                        <th style="width:120px">Precio compra</th>
                        <th style="width:120px">Subtotal</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="items">
                    <tr id="sinItems"><td colspan="8" style="text-align:center;color:#9aa3ab;padding:20px">Aún no has agregado productos.</td></tr>
                </tbody>
            </table>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:16px">
                <div class="totales" style="min-width:260px">
                    <div class="r"><span>Subtotal</span><span id="t_sub">{{ $simMoneda }} 0.00</span></div>
                    <div class="r"><span>IVA ({{ rtrim(rtrim(number_format($empresa->igv ?? 12, 2), '0'), '.') }}%)</span><span id="t_igv">{{ $simMoneda }} 0.00</span></div>
                    <div class="r total"><span>Total</span><span id="t_total">{{ $simMoneda }} 0.00</span></div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success" style="width:auto" id="btnGuardar" disabled>
                    <i class="fa-solid fa-floppy-disk"></i> Registrar compra
                </button>
                <a href="{{ route('compras.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </div>
    </form>

    <!-- Modal Ingreso de Sabores en Bloque -->
    <div id="modalVariantesBloque" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:15px;">
        <div style="background:#fff; border-radius:12px; width:100%; max-width:650px; max-height:85vh; overflow-y:auto; padding:20px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:12px; margin-bottom:15px;">
                <h3 style="margin:0; font-size:1.1rem; color:#1e293b;"><i class="fa-solid fa-layer-group" style="color:#ca8a04;"></i> Ingreso en Bloque de Sabores: <span id="lblModalProdNombre" style="color:#0284c7;"></span></h3>
                <button type="button" onclick="cerrarModalVariantesBloque()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#64748b;">&times;</button>
            </div>
            <div id="modalVariantesBody"></div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; border-top:1px solid #e2e8f0; padding-top:12px;">
                <button type="button" class="btn btn-light" onclick="cerrarModalVariantesBloque()">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="confirmarVariantesBloque()"><i class="fa-solid fa-plus"></i> Agregar Sabores al Detalle</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
const simMoneda = @json($simMoneda);
const IGV = {{ $empresa ? $empresa->tasaIgv() : 0.12 }};
let idx = 0;
const money = v => simMoneda + ' ' + Number(v).toFixed(2);

function onProductoSelected() {
    const sel = document.getElementById('prodSelect');
    const opt = sel.options[sel.selectedIndex];
    const wrapPres = document.getElementById('wrapPresentacionSelect');
    const presSel = document.getElementById('presentacionSelect');
    const wrapSabor = document.getElementById('wrapSaborSelect');
    const saborSel = document.getElementById('saborSelect');

    if (!opt || !opt.value) {
        wrapPres.style.display = 'none';
        wrapSabor.style.display = 'none';
        return;
    }

    // Configurar Sabores / Variantes
    const tieneVariantes = opt.dataset.tienevariantes === '1';
    if (tieneVariantes && opt.dataset.variantes) {
        try {
            const vars = JSON.parse(opt.dataset.variantes);
            if (vars.length > 0) {
                saborSel.innerHTML = vars.map(v => `<option value="${v.id}">🎨 ${v.sabor} (Stock: ${v.stock})</option>`).join('');
                wrapSabor.style.display = 'flex';
            } else {
                wrapSabor.style.display = 'none';
            }
        } catch(e) { wrapSabor.style.display = 'none'; }
    } else {
        wrapSabor.style.display = 'none';
    }

    if (opt.dataset.tieneempaque === '1') {
        const nombreE = opt.dataset.nombreempaque || 'Fardo';
        const cantE = opt.dataset.cantempaque || 1;
        const und = opt.dataset.unidad || 'UND';
        presSel.innerHTML = `
            <option value="EMPAQUE" selected>1 ${nombreE} (x${cantE} ${und})</option>
            <option value="UNIDAD">1 ${und} (Individual)</option>
        `;
        wrapPres.style.display = 'block';
    } else {
        wrapPres.style.display = 'none';
    }
}

function abrirModalVariantesBloque() {
    const sel = document.getElementById('prodSelect');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value || opt.dataset.tienevariantes !== '1') return;

    let vars = [];
    try { vars = JSON.parse(opt.dataset.variantes); } catch(e){}
    if (!vars.length) return;

    const body = document.getElementById('modalVariantesBody');
    const wrapPres = document.getElementById('wrapPresentacionSelect');
    const esEmpaque = wrapPres.style.display !== 'none' && document.getElementById('presentacionSelect').value === 'EMPAQUE';
    const factor = esEmpaque ? (parseInt(opt.dataset.cantempaque) || 1) : 1;
    const unidad = opt.dataset.unidad || 'UND';
    const precioBase = Number(opt.dataset.precio || 0) * factor;

    document.getElementById('lblModalProdNombre').textContent = opt.dataset.nombre;

    let html = `
        <div style="margin-bottom:12px; font-size:12px; color:#64748b;">
            Ingresa las cantidades para cada sabor. Se agregarán automáticamente sólo los sabores con cantidad mayor a 0.
            ${esEmpaque ? `<strong style="color:#0284c7;"> (Presentación: ${opt.dataset.nombreempaque || 'Fardo'} x${factor} ${unidad})</strong>` : ''}
        </div>
        <table class="table" style="width:100%; font-size:13px; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; text-align:left;">
                    <th style="padding:8px;">Sabor / Variante</th>
                    <th style="padding:8px; width:100px;">Stock Actual</th>
                    <th style="padding:8px; width:130px;">Cantidad a Comprar</th>
                    <th style="padding:8px; width:130px;">Precio Compra</th>
                </tr>
            </thead>
            <tbody>`;

    vars.forEach(v => {
        html += `
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:8px;"><strong>🎨 ${v.sabor}</strong></td>
                <td style="padding:8px;"><span class="badge" style="background:#f1f5f9;color:#475569;">${v.stock} ${unidad}</span></td>
                <td style="padding:8px;">
                    <input type="number" min="0" value="0" class="form-control mv-cant" data-varid="${v.id}" data-varsabor="${v.sabor}" style="padding:4px 8px; font-weight:700; text-align:center;">
                </td>
                <td style="padding:8px;">
                    <input type="number" min="0" step="0.01" value="${precioBase.toFixed(2)}" class="form-control mv-precio" style="padding:4px 8px; text-align:right;">
                </td>
            </tr>`;
    });

    html += `</tbody></table>`;
    body.innerHTML = html;
    document.getElementById('modalVariantesBloque').style.display = 'flex';
}

function cerrarModalVariantesBloque() {
    document.getElementById('modalVariantesBloque').style.display = 'none';
}

function confirmarVariantesBloque() {
    const sel = document.getElementById('prodSelect');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const rows = document.querySelectorAll('#modalVariantesBody tbody tr');
    let agregados = 0;

    rows.forEach(r => {
        const cantInput = r.querySelector('.mv-cant');
        const precioInput = r.querySelector('.mv-precio');
        const cant = parseInt(cantInput.value) || 0;
        const precio = parseFloat(precioInput.value) || 0;
        const varId = cantInput.dataset.varid;
        const varSabor = cantInput.dataset.varsabor;

        if (cant > 0) {
            agregarLineaIndividual(opt, varId, varSabor, cant, precio);
            agregados++;
        }
    });

    cerrarModalVariantesBloque();
    if (agregados > 0) {
        sel.value = '';
        sel.dispatchEvent(new Event('change'));
        onProductoSelected();
        calc();
    }
}

function addLinea() {
    const sel = document.getElementById('prodSelect');
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;

    const wrapSabor = document.getElementById('wrapSaborSelect');
    const saborSel = document.getElementById('saborSelect');
    const esSabor = wrapSabor.style.display !== 'none' && saborSel.value;
    const saborId = esSabor ? saborSel.value : null;
    const saborNombre = esSabor ? saborSel.options[saborSel.selectedIndex].text : '';

    const wrapPres = document.getElementById('wrapPresentacionSelect');
    const esEmpaque = wrapPres.style.display !== 'none' && document.getElementById('presentacionSelect').value === 'EMPAQUE';
    const factor = esEmpaque ? (parseInt(opt.dataset.cantempaque) || 1) : 1;
    const precioBase = Number(opt.dataset.precio || 0) * (esEmpaque ? factor : 1);

    agregarLineaIndividual(opt, saborId, saborNombre, 1, precioBase);

    sel.value = '';
    sel.dispatchEvent(new Event('change'));
    onProductoSelected();
    calc();
}

function agregarLineaIndividual(opt, saborId, saborNombre, cantVal, precioVal) {
    const pidKey = opt.value + (saborId ? ('-var-' + saborId) : '');
    const existente = document.querySelector(`tr[data-linekey="${pidKey}"]`);
    if (existente) {
        const inputCant = existente.querySelector('.lc-input');
        inputCant.value = parseInt(inputCant.value || 0) + cantVal;
        recalcLinea(inputCant);
        return;
    }

    const esPerecedero = opt.dataset.perecedero === '1';
    const defaultLote = esPerecedero ? 'LOTE-' + new Date().getFullYear() + '-' + Math.floor(Math.random() * 900 + 100) : '';

    const wrapPres = document.getElementById('wrapPresentacionSelect');
    const esEmpaque = wrapPres.style.display !== 'none' && document.getElementById('presentacionSelect').value === 'EMPAQUE';
    const factor = esEmpaque ? (parseInt(opt.dataset.cantempaque) || 1) : 1;
    const nombreEmpaque = opt.dataset.nombreempaque || 'Fardo';

    document.getElementById('sinItems')?.remove();
    const i = idx++;
    const tr = document.createElement('tr');
    tr.dataset.pid = opt.value;
    tr.dataset.linekey = pidKey;
    tr.dataset.factor = factor;

    let badgeEmpaque = esEmpaque ? `<br><span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:10px"><i class="fa-solid fa-boxes-packing"></i> Compra por ${nombreEmpaque} (x${factor} ${opt.dataset.unidad})</span>` : '';
    let badgeSabor = saborNombre ? `<br><span class="badge" style="background:#fef9c3;color:#713f12;font-size:10px"><i class="fa-solid fa-palette"></i> ${saborNombre}</span>` : '';

    tr.innerHTML = `
        <td><span class="badge-soft">${opt.dataset.codigo}</span>
            <input type="hidden" class="l-factor" value="${factor}">
            <input type="hidden" name="items[${i}][producto_id]" value="${opt.value}">
            <input type="hidden" name="items[${i}][producto_variante_id]" value="${saborId || ''}">
            <input type="hidden" name="items[${i}][cantidad]" class="l-cant-base" value="${factor}">
            <input type="hidden" name="items[${i}][precio]" class="l-unit-precio" value="${opt.dataset.precio}">
        </td>
        <td>${opt.dataset.nombre} ${esPerecedero ? '<span class="badge" style="background:#fef3c7;color:#92400e;font-size:10px">Perecedero</span>' : ''} ${badgeSabor} ${badgeEmpaque}<br><span style="color:#9aa3ab;font-size:11px">Stock actual: ${opt.dataset.stock} ${opt.dataset.unidad}</span></td>
        <td><input type="text" placeholder="Ej: LOTE-001" value="${defaultLote}" name="items[${i}][codigo_lote]" class="form-control" style="padding:6px" ${esPerecedero ? 'required' : ''}></td>
        <td><input type="date" name="items[${i}][fecha_vencimiento]" class="form-control" style="padding:6px" ${esPerecedero ? 'required' : ''}></td>
        <td>
            <input type="number" min="1" value="${cantVal}" class="form-control lc-input" style="padding:6px" oninput="recalcLinea(this)">
            <small class="lbl-conversion" style="color:#0369a1;font-weight:600;display:${esEmpaque ? 'block' : 'none'};font-size:10px">= ${factor * cantVal} ${opt.dataset.unidad}</small>
        </td>
        <td><input type="number" min="0" step="0.01" value="${Number(precioVal).toFixed(2)}" class="form-control lp-input" style="padding:6px" oninput="recalcLinea(this)"></td>
        <td class="sub"><strong>${money(0)}</strong></td>
        <td><button type="button" class="btn-icon del" onclick="this.closest('tr').remove();calc()"><i class="fa-solid fa-xmark"></i></button></td>`;
    document.getElementById('items').appendChild(tr);
    recalcLinea(tr.querySelector('.lc-input'));
}

function recalcLinea(elem) {
    const tr = elem.closest('tr');
    const factor = parseInt(tr.querySelector('.l-factor').value) || 1;
    const cantInput = parseInt(tr.querySelector('.lc-input').value) || 0;
    const precioInput = parseFloat(tr.querySelector('.lp-input').value) || 0;

    const cantBase = cantInput * factor;
    const unitPrecio = factor > 0 ? (precioInput / factor) : precioInput;

    tr.querySelector('.l-cant-base').value = cantBase;
    tr.querySelector('.l-unit-precio').value = unitPrecio.toFixed(4);

    const lbl = tr.querySelector('.lbl-conversion');
    if (lbl && factor > 1) {
        lbl.textContent = `= ${cantBase} unidades en bodega`;
    }

    const subtotal = cantInput * precioInput;
    tr.querySelector('.sub').innerHTML = `<strong>${money(subtotal)}</strong>`;
}

function calc() {
    let total = 0;
    document.querySelectorAll('#items tr[data-pid]').forEach(tr => {
        const inputVal = Number(tr.querySelector('.lc-input').value) || 0;
        const p = Number(tr.querySelector('.lp-input').value) || 0;
        total += inputVal * p;
        recalcLinea(tr.querySelector('.lc-input'));
    });
    const subtotal = IGV > 0 ? (total / (1 + IGV)) : total;
    const igv = total - subtotal;
    document.getElementById('t_sub').textContent = money(subtotal);
    document.getElementById('t_igv').textContent = money(igv);
    document.getElementById('t_total').textContent = money(total);
    document.getElementById('btnGuardar').disabled = document.querySelectorAll('#items tr[data-pid]').length === 0;
}

document.addEventListener('DOMContentLoaded', () => {
    initSearchableSelect('proveedor_id', { placeholder: 'Buscar proveedor...' });
    initSearchableSelect('prodSelect', { placeholder: 'Buscar producto por nombre o código...' });
});
</script>
@endpush
