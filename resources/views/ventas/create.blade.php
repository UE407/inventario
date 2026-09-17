@extends('layouts.app')

@section('title', 'Nueva Venta Backoffice')

@php
    $simMoneda = $empresa->moneda ?? 'Q';
@endphp

@section('content')
    <div class="page-head">
        <h1>NUEVA VENTA (BACKOFFICE)</h1>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <span class="sep">/</span> <a href="{{ route('ventas.index') }}">Ventas</a>
            <span class="sep">/</span> Nueva Venta Backoffice
        </div>
    </div>

    @if ($errors->any())
        <div class="flash error"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
    @endif

    <div class="form-card" style="max-width:100%">
        <form id="formVentaBackoffice">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label>Cliente</label>
                    <select id="cliente_id" class="form-control">
                        <option value="">Consumidor final / Cliente varios</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->tipo_documento }}: {{ $c->numero_documento ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo de Comprobante <span style="color:#e74c3c">*</span></label>
                    <select id="tipo_comprobante" class="form-control" required>
                        <option value="TICKET">Ticket</option>
                        <option value="BOLETA">Boleta</option>
                        <option value="FACTURA">Factura (FEL SAT)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Método de Pago <span style="color:#e74c3c">*</span></label>
                    <select id="metodo_pago" class="form-control" onchange="toggleEfectivo()" required>
                        <option value="EFECTIVO">Efectivo</option>
                        <option value="TARJETA">Tarjeta de Débito/Crédito</option>
                        <option value="TRANSFERENCIA">Transferencia Bancaria</option>
                    </select>
                </div>
                <div class="form-group" id="groupEfectivo">
                    <label>Efectivo Recibido ({{ $simMoneda }})</label>
                    <input type="number" step="0.01" min="0" id="efectivo_recibido" class="form-control" placeholder="0.00" oninput="calc()">
                </div>
            </div>

            <h3 style="margin:22px 0 12px;color:#4a5560;font-size:14px"><span class="dot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--brand);margin-right:6px"></span> Agregar Productos</h3>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">
                <div style="flex:2;min-width:250px">
                    <select id="prodSelect" class="form-control" onchange="onProductoSelected()">
                        <option value="">Selecciona un producto para agregar...</option>
                        @foreach($productos as $prod)
                            <option value="{{ $prod->id }}"
                                data-codigo="{{ $prod->codigo }}"
                                data-nombre="{{ $prod->nombre }}"
                                data-precio="{{ $prod->precio_venta }}"
                                data-stock="{{ $prod->stock }}"
                                data-unidad="{{ $prod->unidad }}"
                                data-tieneempaque="{{ $prod->tiene_empaque ? '1' : '0' }}"
                                data-nombreempaque="{{ $prod->nombre_empaque ?? 'Fardo' }}"
                                data-cantempaque="{{ $prod->cant_por_empaque ?? 1 }}"
                                data-precioempaque="{{ $prod->precio_venta_empaque ?? ($prod->precio_venta * ($prod->cant_por_empaque ?? 1)) }}"
                                data-perecedero="{{ $prod->es_perecedero ? '1' : '0' }}"
                                data-tienevariantes="{{ $prod->tiene_variantes ? '1' : '0' }}"
                                data-variantes='@json($prod->variantes)'
                                data-lotes='@json($prod->lotes)'>
                                {{ $prod->codigo }} · {{ $prod->nombre }} {{ $prod->tiene_variantes ? '🎨 [Multi-sabor]' : '' }} {{ $prod->tiene_empaque ? '📦 ['.$prod->nombre_empaque.' x'.$prod->cant_por_empaque.']' : '' }} {{ $prod->es_perecedero ? '🥦 (Perecedero)' : '' }} - Stock: {{ $prod->stock }} {{ $prod->unidad }}
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
                        <option value="EMPAQUE">Fardo / Caja</option>
                        <option value="UNIDAD">Unidad Individual</option>
                    </select>
                </div>

                <div id="wrapLoteSelect" style="flex:1.5;min-width:200px;display:none;">
                    <select id="loteSelect" class="form-control" style="border-color:#e67e22;">
                        <option value="">Auto FEFO (Recomendado: Lote más próximo a vencer)</option>
                    </select>
                </div>

                <button type="button" class="btn btn-primary btn-sm" style="width:auto" onclick="addLinea()">
                    <i class="fa-solid fa-plus"></i> Agregar Producto
                </button>
            </div>

            <div class="panel-scroll">
                <table class="table" id="tablaItems">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th style="width:220px">Lote Seleccionado</th>
                            <th style="width:100px">Cantidad</th>
                            <th style="width:120px">Precio Venta</th>
                            <th style="width:120px">Subtotal</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="items">
                        <tr id="sinItems"><td colspan="7" style="text-align:center;color:#9aa3ab;padding:20px">Aún no has agregado productos a la venta.</td></tr>
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-top:20px;flex-wrap:wrap;gap:15px">
                <div style="max-width:300px">
                    <label style="font-size:0.85rem;font-weight:600">Descuento Global ({{ $simMoneda }})</label>
                    <input type="number" id="descuento" min="0" step="0.01" value="0" class="form-control" oninput="calc()">
                    <div id="vueltoWrap" style="margin-top:10px;font-weight:700;color:#10b981;font-size:1.1rem;display:none;">
                        Vuelto: <span id="lblVuelto">{{ $simMoneda }} 0.00</span>
                    </div>
                </div>

                <div class="totales" style="min-width:260px">
                    <div class="r"><span>Subtotal</span><span id="t_sub">{{ $simMoneda }} 0.00</span></div>
                    <div class="r"><span>Descuento</span><span id="t_desc">{{ $simMoneda }} 0.00</span></div>
                    <div class="r"><span>IVA ({{ rtrim(rtrim(number_format($empresa->igv ?? 12, 2), '0'), '.') }}%)</span><span id="t_igv">{{ $simMoneda }} 0.00</span></div>
                    <div class="r total"><span>Total</span><span id="t_total">{{ $simMoneda }} 0.00</span></div>
                </div>
            </div>

            <div class="form-actions" style="margin-top:20px">
                <button type="button" class="btn btn-success" style="width:auto" id="btnGuardar" onclick="guardarVenta()" disabled>
                    <i class="fa-solid fa-floppy-disk"></i> Registrar y Emitir Venta
                </button>
                <a href="{{ route('ventas.index') }}" class="btn btn-light">Cancelar</a>
            </div>
            <div id="msg" style="margin-top:10px;font-weight:600;"></div>
        </form>
    </div>

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
const URL_STORE = "{{ route('ventas.store') }}";
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let idx = 0;

const money = v => simMoneda + ' ' + Number(v).toFixed(2);

function toggleEfectivo() {
    const esEfec = document.getElementById('metodo_pago').value === 'EFECTIVO';
    document.getElementById('groupEfectivo').style.display = esEfec ? 'block' : 'none';
    document.getElementById('vueltoWrap').style.display = esEfec ? 'block' : 'none';
    calc();
}

function onProductoSelected() {
    const sel = document.getElementById('prodSelect');
    const opt = sel.options[sel.selectedIndex];
    const wrapLote = document.getElementById('wrapLoteSelect');
    const loteSel = document.getElementById('loteSelect');
    const wrapPres = document.getElementById('wrapPresentacionSelect');
    const presSel = document.getElementById('presentacionSelect');
    const wrapSabor = document.getElementById('wrapSaborSelect');
    const saborSel = document.getElementById('saborSelect');

    loteSel.innerHTML = '<option value="">Auto FEFO (Lote más próximo a vencer)</option>';

    if (!opt.value) {
        wrapLote.style.display = 'none';
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
                saborSel.innerHTML = vars.map(v => `<option value="${v.id}" data-stk="${v.stock}">🎨 ${v.sabor} (Stock: ${v.stock})</option>`).join('');
                wrapSabor.style.display = 'flex';
            } else {
                wrapSabor.style.display = 'none';
            }
        } catch(e) { wrapSabor.style.display = 'none'; }
    } else {
        wrapSabor.style.display = 'none';
    }

    // Configurar Selector de Presentación (Empaque vs Unidad)
    const tieneEmpaque = opt.dataset.tieneempaque === '1';
    if (tieneEmpaque) {
        const nomEmp = opt.dataset.nombreempaque || 'Fardo';
        const cantEmp = opt.dataset.cantempaque || '1';
        const precEmp = Number(opt.dataset.precioempaque || 0);
        const precUni = Number(opt.dataset.precio || 0);

        presSel.innerHTML = `
            <option value="EMPAQUE">📦 ${nomEmp} (x${cantEmp} ${opt.dataset.unidad}) - ${money(precEmp)}</option>
            <option value="UNIDAD">🧪 Unidad Individual - ${money(precUni)}</option>
        `;
        wrapPres.style.display = 'block';
    } else {
        presSel.innerHTML = `<option value="UNIDAD">🧪 Unidad Individual - ${money(opt.dataset.precio)}</option>`;
        wrapPres.style.display = 'none';
    }

    const esPerecedero = opt.dataset.perecedero === '1';
    if (esPerecedero && opt.dataset.lotes) {
        try {
            const lotes = JSON.parse(opt.dataset.lotes);
            if (lotes.length > 0) {
                lotes.forEach(l => {
                    const f = l.fecha_vencimiento ? l.fecha_vencimiento.substring(0, 10) : '';
                    loteSel.innerHTML += `<option value="${l.id}">${l.codigo_lote} (Vence: ${f} - Stock: ${l.stock_actual})</option>`;
                });
                wrapLote.style.display = 'block';
                return;
            }
        } catch(e){}
    }
    wrapLote.style.display = 'none';
}

function abrirModalVariantesBloque() {
    const sel = document.getElementById('prodSelect');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value || opt.dataset.tienevariantes !== '1') return;

    let vars = [];
    try { vars = JSON.parse(opt.dataset.variantes); } catch(e){}
    if (!vars.length) return;

    const body = document.getElementById('modalVariantesBody');
    const presSel = document.getElementById('presentacionSelect');
    const esEmpaque = (opt.dataset.tieneempaque === '1') && (presSel.value === 'EMPAQUE');
    const factor = esEmpaque ? (parseInt(opt.dataset.cantempaque) || 1) : 1;
    const precioPres = esEmpaque ? parseFloat(opt.dataset.precioempaque) : parseFloat(opt.dataset.precio);
    const nomEmpaque = esEmpaque ? (opt.dataset.nombreempaque || 'Fardo') : 'Unidad';
    const unidad = opt.dataset.unidad || 'UND';

    document.getElementById('lblModalProdNombre').textContent = opt.dataset.nombre;

    let html = `
        <div style="margin-bottom:12px; font-size:12px; color:#64748b;">
            Ingresa las cantidades para cada sabor. Se agregarán automáticamente sólo los sabores con cantidad mayor a 0.
            <strong style="color:#0284c7;"> (Presentación: ${nomEmpaque} x${factor} ${unidad})</strong>
        </div>
        <table class="table" style="width:100%; font-size:13px; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; text-align:left;">
                    <th style="padding:8px;">Sabor / Variante</th>
                    <th style="padding:8px; width:110px;">Stock Disp.</th>
                    <th style="padding:8px; width:120px;">Cant. Venta</th>
                    <th style="padding:8px; width:130px;">Precio Venta</th>
                </tr>
            </thead>
            <tbody>`;

    vars.forEach(v => {
        const stockBase = v.stock;
        const maxCant = factor > 1 ? Math.floor(stockBase / factor) : stockBase;
        html += `
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:8px;"><strong>🎨 ${v.sabor}</strong></td>
                <td style="padding:8px;"><span class="badge" style="background:#f1f5f9;color:#475569;">${v.stock} ${unidad}</span></td>
                <td style="padding:8px;">
                    <input type="number" min="0" max="${maxCant}" value="0" class="form-control mv-cant" data-varid="${v.id}" data-varsabor="${v.sabor}" data-stk="${v.stock}" style="padding:4px 8px; font-weight:700; text-align:center;">
                </td>
                <td style="padding:8px;">
                    <input type="number" min="0" step="0.01" value="${precioPres.toFixed(2)}" class="form-control mv-precio" style="padding:4px 8px; text-align:right;">
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
        const varStk = parseInt(cantInput.dataset.stk) || 0;

        if (cant > 0) {
            agregarLineaVentaIndividual(opt, varId, varSabor, varStk, cant, precio);
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
    const saborStk = (esSabor && saborSel.selectedIndex >= 0) ? (parseInt(saborSel.options[saborSel.selectedIndex].dataset.stk) || 0) : null;

    const presSel = document.getElementById('presentacionSelect');
    const esEmpaque = (opt.dataset.tieneempaque === '1') && (presSel.value === 'EMPAQUE');
    const precioPres = esEmpaque ? parseFloat(opt.dataset.precioempaque) : parseFloat(opt.dataset.precio);
    const stockBase = (saborStk !== null) ? saborStk : (parseInt(opt.dataset.stock) || 0);

    agregarLineaVentaIndividual(opt, saborId, saborNombre, stockBase, 1, precioPres);

    sel.value = '';
    sel.dispatchEvent(new Event('change'));
    onProductoSelected();
    calc();
}

function agregarLineaVentaIndividual(opt, saborId, saborNombre, stockBase, cantVal, precioPres) {
    const presSel = document.getElementById('presentacionSelect');
    const esEmpaque = (opt.dataset.tieneempaque === '1') && (presSel.value === 'EMPAQUE');
    const factor = esEmpaque ? (parseInt(opt.dataset.cantempaque) || 1) : 1;
    const presNombre = esEmpaque ? (opt.dataset.nombreempaque || 'Empaque') : 'Unidad';
    const maxCant = factor > 1 ? Math.floor(stockBase / factor) : stockBase;

    const loteSel = document.getElementById('loteSelect');
    const loteId = loteSel.value || null;
    const loteTxt = loteSel.selectedIndex > 0 ? loteSel.options[loteSel.selectedIndex].text : 'Auto FEFO (Más próximo a vencer)';

    document.getElementById('sinItems')?.remove();
    const tr = document.createElement('tr');
    tr.dataset.pid = opt.value;
    tr.dataset.varid = saborId || '';
    tr.dataset.loteid = loteId || '';
    tr.dataset.factor = factor;
    tr.dataset.presnombre = presNombre;
    tr.innerHTML = `
        <td><span class="badge-soft">${opt.dataset.codigo}</span>
            <input type="hidden" class="l-pid" value="${opt.value}">
            <input type="hidden" class="l-varid" value="${saborId || ''}">
            <input type="hidden" class="l-loteid" value="${loteId || ''}">
        </td>
        <td>
            ${opt.dataset.nombre}
            ${saborNombre ? `<span class="badge" style="background:#fef9c3; color:#713f12; font-size:10px; margin-left:4px;"><i class="fa-solid fa-palette"></i> ${saborNombre}</span>` : ''}
            <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:10px; margin-left:4px;">${presNombre} (x${factor})</span>
            <br><span style="color:#9aa3ab;font-size:11px">Stock total: ${stockBase} ${opt.dataset.unidad}</span>
        </td>
        <td><span class="badge" style="background:${loteId ? '#e0f2fe' : '#fef3c7'}; color:${loteId ? '#0369a1' : '#92400e'}; font-size:11px">${loteTxt}</span></td>
        <td><input type="number" min="1" max="${maxCant > 0 ? maxCant : 1}" value="${cantVal}" class="form-control lc" style="padding:6px" oninput="calc()"></td>
        <td><input type="number" min="0" step="0.01" value="${precioPres.toFixed(2)}" class="form-control lp" style="padding:6px" oninput="calc()"></td>
        <td class="sub"><strong>${money(0)}</strong></td>
        <td><button type="button" class="btn-icon del" onclick="this.closest('tr').remove();calc()"><i class="fa-solid fa-xmark"></i></button></td>`;
    document.getElementById('items').appendChild(tr);
}

function calc() {
    let subtotal = 0;
    document.querySelectorAll('#items tr[data-pid]').forEach(tr => {
        const c = Number(tr.querySelector('.lc').value) || 0;
        const p = Number(tr.querySelector('.lp').value) || 0;
        const s = c * p;
        subtotal += s;
        tr.querySelector('.sub').innerHTML = `<strong>${money(s)}</strong>`;
    });

    let desc = Number(document.getElementById('descuento').value) || 0;
    desc = Math.min(desc, subtotal);
    const total = subtotal - desc;
    const igv = IGV > 0 ? (total - (total / (1 + IGV))) : 0;

    document.getElementById('t_sub').textContent = money(subtotal);
    document.getElementById('t_desc').textContent = money(desc);
    document.getElementById('t_igv').textContent = money(igv);
    document.getElementById('t_total').textContent = money(total);

    const esEfec = document.getElementById('metodo_pago').value === 'EFECTIVO';
    const efec = Number(document.getElementById('efectivo_recibido').value) || 0;
    const vuelto = (esEfec && efec >= total) ? (efec - total) : 0;
    document.getElementById('lblVuelto').textContent = money(vuelto);

    const filas = document.querySelectorAll('#items tr[data-pid]').length;
    document.getElementById('btnGuardar').disabled = filas === 0 || (esEfec && efec > 0 && efec < total);
}

async function guardarVenta() {
    const btn = document.getElementById('btnGuardar');
    const msg = document.getElementById('msg');
    msg.innerHTML = '';
    btn.disabled = true;

    const items = [];
    document.querySelectorAll('#items tr[data-pid]').forEach(tr => {
        const cPres = Number(tr.querySelector('.lc').value) || 0;
        const pPres = Number(tr.querySelector('.lp').value) || 0;
        const factor = parseInt(tr.dataset.factor) || 1;
        const cantBase = cPres * factor;
        const precioUnitBase = factor > 0 ? (pPres / factor) : pPres;

        items.push({
            producto_id: tr.querySelector('.l-pid').value,
            producto_variante_id: tr.querySelector('.l-varid').value || null,
            lote_id: tr.querySelector('.l-loteid').value || null,
            cantidad: cantBase,
            precio: precioUnitBase,
        });
    });

    const payload = {
        cliente_id: document.getElementById('cliente_id').value || null,
        tipo_comprobante: document.getElementById('tipo_comprobante').value,
        metodo_pago: document.getElementById('metodo_pago').value,
        descuento: Number(document.getElementById('descuento').value) || 0,
        efectivo_recibido: Number(document.getElementById('efectivo_recibido').value) || null,
        items: items
    };

    try {
        const res = await fetch(URL_STORE, {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json'},
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok) {
            msg.style.color = '#ef4444';
            msg.textContent = data.message || 'Error al procesar la venta.';
            btn.disabled = false;
            return;
        }
        window.location = data.redirect;
    } catch(e) {
        msg.style.color = '#ef4444';
        msg.textContent = 'Error de conexión con el servidor.';
        btn.disabled = false;
    }
}

toggleEfectivo();
document.addEventListener('DOMContentLoaded', () => {
    initSearchableSelect('cliente_id', { placeholder: 'Buscar cliente por nombre, NIT o DPI...' });
    initSearchableSelect('prodSelect', { placeholder: 'Buscar producto por nombre o código...' });
});
</script>
@endpush
