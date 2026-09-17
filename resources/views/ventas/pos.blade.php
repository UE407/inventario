@extends('layouts.app')

@section('title', 'Punto de Venta')

@php
    $simMoneda = $empresa->moneda ?? 'Q';
@endphp

@section('content')
    <div class="page-head">
        <h1>PUNTO DE VENTA</h1>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <span class="sep">/</span> Ventas <span class="sep">/</span> POS
        </div>
    </div>

    <div class="pos">
        {{-- ===== Izquierda: productos ===== --}}
        <div class="pos-left">
            <div class="pos-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="buscador" placeholder="Buscar producto por nombre o código..." autofocus autocomplete="off">
            </div>
            <div class="pos-grid" id="grid"></div>
        </div>

        {{-- ===== Derecha: carrito ===== --}}
        <div class="cart">
            <div class="cart-head">
                <i class="fa-solid fa-cart-shopping"></i> Carrito
                <span class="count" id="count">0</span>
            </div>

            <div class="cart-items" id="items">
                <div class="cart-empty" id="empty">
                    <i class="fa-solid fa-basket-shopping" style="font-size:34px;display:block;margin-bottom:10px;opacity:.5"></i>
                    Agrega productos para empezar
                </div>
            </div>

            <div class="cart-foot">
                <div class="fields">
                    <div class="full">
                        <label>Cliente</label>
                        <select id="cliente_id">
                            <option value="">Consumidor final / Cliente varios</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}" data-doc="{{ $c->numero_documento }}">
                                    {{ $c->nombre }} {{ $c->numero_documento ? '('.($c->tipo_documento ?? 'NIT/DPI').': '.$c->numero_documento.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Comprobante</label>
                        <select id="tipo_comprobante">
                            <option value="TICKET">Ticket</option>
                            <option value="BOLETA">Boleta</option>
                            <option value="FACTURA">Factura</option>
                        </select>
                    </div>
                    <div>
                        <label>Descuento ({{ $simMoneda }})</label>
                        <input type="number" id="descuento" min="0" step="0.01" value="0">
                    </div>
                    <div class="full">
                        <label>Método de pago</label>
                        <input type="hidden" id="metodo_pago" value="EFECTIVO">
                        <div class="pay-chips">
                            <div class="pay-chip active" data-val="EFECTIVO"><i class="fa-solid fa-money-bill-wave"></i> Efectivo</div>
                            <div class="pay-chip" data-val="TARJETA"><i class="fa-regular fa-credit-card"></i> Tarjeta</div>
                            <div class="pay-chip" data-val="TRANSFERENCIA"><i class="fa-solid fa-building-columns"></i> Transferencia</div>
                        </div>
                    </div>
                    <div class="full" id="cashWrap">
                        <label>Efectivo recibido ({{ $simMoneda }})</label>
                        <input type="number" id="efectivo" min="0" step="0.01" placeholder="0.00">
                        <div class="vuelto-row"><span>Vuelto</span><span id="vuelto">{{ $simMoneda }} 0.00</span></div>
                    </div>
                </div>

                <div class="totales">
                    <div class="r"><span>Subtotal</span><span id="t_sub">{{ $simMoneda }} 0.00</span></div>
                    <div class="r"><span>Descuento</span><span id="t_desc">{{ $simMoneda }} 0.00</span></div>
                    <div class="r"><span>IVA ({{ rtrim(rtrim(number_format($empresa->igv ?? 12, 2), '0'), '.') }}%)</span><span id="t_igv">{{ $simMoneda }} 0.00</span></div>
                    <div class="r total"><span>Total</span><span id="t_total">{{ $simMoneda }} 0.00</span></div>
                </div>

                <button class="btn-cobrar" id="cobrar" disabled>
                    <i class="fa-solid fa-circle-check"></i> Cobrar
                    <span id="btn_total"></span>
                </button>
                <div class="pos-msg" id="msg"></div>
            </div>
        </div>
    </div>

    <!-- Modal POS Sabores -->
    <div id="modalPosVariantes" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:15px;">
        <div style="background:#fff; border-radius:12px; width:100%; max-width:550px; max-height:85vh; overflow-y:auto; padding:20px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:12px; margin-bottom:15px;">
                <h3 style="margin:0; font-size:1.1rem; color:#1e293b;"><i class="fa-solid fa-layer-group" style="color:#ca8a04;"></i> Selecciona Sabores: <span id="lblModalPosProdNombre" style="color:#0284c7;"></span></h3>
                <button type="button" onclick="cerrarModalPosVariantes()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#64748b;">&times;</button>
            </div>
            <div id="modalPosVariantesBody"></div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; border-top:1px solid #e2e8f0; padding-top:12px;">
                <button type="button" class="btn btn-light" onclick="cerrarModalPosVariantes()">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="confirmarPosVariantes()"><i class="fa-solid fa-cart-plus"></i> Agregar Sabores al Carrito</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
const simMoneda = @json($simMoneda);
const IGV = {{ $empresa ? $empresa->tasaIgv() : 0.12 }};
const URL_BUSCAR = "{{ route('ventas.buscar') }}";
const URL_STORE  = "{{ route('ventas.store') }}";
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

let cart = [];   // {cartKey, id, nombre, precio, stock, cantidad, producto_variante_id...}
let pSelectedPos = null;
const money = v => simMoneda + ' ' + Number(v).toFixed(2);

/* Paleta para el avatar del producto (color estable por nombre) */
const AVA = ['#1583b8','#2ba6a4','#7a5cf0','#e8734a','#e05c8a','#3aa76d','#d9a520','#5a7fd6'];
function avaColor(txt){
    let h = 0;
    for (let i = 0; i < txt.length; i++) h = (h * 31 + txt.charCodeAt(i)) % AVA.length;
    return AVA[h];
}
function inicial(txt){ return (txt.trim()[0] || '?').toUpperCase(); }

/* ---- Buscar productos ---- */
const grid = document.getElementById('grid');
let timer = null;

async function buscar(q = '') {
    const res = await fetch(`${URL_BUSCAR}?q=${encodeURIComponent(q)}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
    const data = await res.json();
    grid.innerHTML = data.length ? '' : '<div style="color:#9aa3ab;padding:24px;grid-column:1/-1;text-align:center">Sin resultados.</div>';
    data.forEach(p => {
        const sin = p.stock <= 0;
        const min = p.stock_minimo ?? 5;
        const stkClass = sin ? 'out' : (p.stock <= min ? 'low' : 'ok');
        const el = document.createElement('div');
        el.className = 'pos-prod' + (sin ? ' sinstock' : '');
        el.innerHTML = `
            <div class="pp-top">
                <div class="pp-ava" style="background:${avaColor(p.nombre)}">${inicial(p.nombre)}</div>
                <span class="stk ${stkClass}">${sin ? 'Sin stock' : 'stock ' + p.stock}</span>
            </div>
            <div class="code">${p.codigo}</div>
            <div class="name">${p.nombre} ${p.tiene_variantes ? '🎨' : ''}</div>
            <div class="pp-bot">
                <div class="price">${money(p.precio_venta)}</div>
                <span class="pp-add"><i class="fa-solid fa-plus"></i></span>
            </div>`;
        if (!sin) el.onclick = () => addItem(p);
        grid.appendChild(el);
    });
}

document.getElementById('buscador').addEventListener('input', e => {
    clearTimeout(timer);
    timer = setTimeout(() => buscar(e.target.value.trim()), 250);
});

/* ---- Método de pago (chips) ---- */
function toggleCash() {
    const esEfectivo = document.getElementById('metodo_pago').value === 'EFECTIVO';
    document.getElementById('cashWrap').style.display = esEfectivo ? 'block' : 'none';
}
document.querySelectorAll('.pay-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.pay-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        document.getElementById('metodo_pago').value = chip.dataset.val;
        toggleCash();
        render();
    });
});

/* ---- Modal POS Variantes ---- */
function abrirModalPosVariantes(p) {
    pSelectedPos = p;
    document.getElementById('lblModalPosProdNombre').textContent = p.nombre;
    const body = document.getElementById('modalPosVariantesBody');

    let html = `
        <div style="margin-bottom:12px; font-size:12px; color:#64748b;">
            Ingresa las cantidades para cada sabor y agrégalas al carrito en bloque.
        </div>
        <table class="table" style="width:100%; font-size:13px; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; text-align:left;">
                    <th style="padding:8px;">Sabor / Variante</th>
                    <th style="padding:8px; width:100px;">Stock</th>
                    <th style="padding:8px; width:130px;">Cantidad</th>
                </tr>
            </thead>
            <tbody>`;

    p.variantes.forEach(v => {
        html += `
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:8px;"><strong>🎨 ${v.sabor}</strong></td>
                <td style="padding:8px;"><span class="badge" style="background:#f1f5f9;color:#475569;">${v.stock}</span></td>
                <td style="padding:8px;">
                    <input type="number" min="0" max="${v.stock}" value="0" class="form-control pos-mv-cant" data-varid="${v.id}" data-varsabor="${v.sabor}" data-varstk="${v.stock}" style="padding:4px 8px; font-weight:700; text-align:center;">
                </td>
            </tr>`;
    });

    html += `</tbody></table>`;
    body.innerHTML = html;
    document.getElementById('modalPosVariantes').style.display = 'flex';
}

function cerrarModalPosVariantes() {
    document.getElementById('modalPosVariantes').style.display = 'none';
    pSelectedPos = null;
}

function confirmarPosVariantes() {
    if (!pSelectedPos) return;
    const p = pSelectedPos;
    const rows = document.querySelectorAll('#modalPosVariantesBody tbody tr');
    let agregados = 0;

    rows.forEach(r => {
        const cantInput = r.querySelector('.pos-mv-cant');
        const cant = parseInt(cantInput.value) || 0;
        const varId = Number(cantInput.dataset.varid);
        const varSabor = cantInput.dataset.varsabor;
        const varStk = Number(cantInput.dataset.varstk) || 0;

        if (cant > 0) {
            const key = p.id + '_var_' + varId;
            const found = cart.find(i => i.cartKey === key);
            if (found) {
                found.cantidad += cant;
            } else {
                cart.push({
                    cartKey: key,
                    id: p.id,
                    nombre: `${p.nombre} (${varSabor})`,
                    precio_venta: Number(p.precio_venta),
                    tiene_empaque: !!p.tiene_empaque,
                    nombre_empaque: p.nombre_empaque || 'Fardo',
                    cant_por_empaque: Number(p.cant_por_empaque || 1),
                    precio_venta_empaque: Number(p.precio_venta_empaque || (p.precio_venta * (p.cant_por_empaque || 1))),
                    presentacion: 'UNIDAD',
                    tiene_variantes: true,
                    variantes: p.variantes || [],
                    producto_variante_id: varId,
                    stock: varStk,
                    cantidad: cant,
                    es_perecedero: !!p.es_perecedero,
                    lotes: p.lotes || [],
                    lote_id: null
                });
            }
            agregados++;
        }
    });

    cerrarModalPosVariantes();
    if (agregados > 0) render();
}

/* ---- Carrito ---- */
function addItem(p) {
    if (p.tiene_variantes && p.variantes && p.variantes.length > 0) {
        abrirModalPosVariantes(p);
        return;
    }

    const key = p.id + '_var_none';
    const found = cart.find(i => i.cartKey === key);
    if (found) {
        if (found.cantidad < p.stock) found.cantidad++;
    } else {
        cart.push({
            cartKey: key,
            id: p.id,
            nombre: p.nombre,
            precio_venta: Number(p.precio_venta),
            tiene_empaque: !!p.tiene_empaque,
            nombre_empaque: p.nombre_empaque || 'Fardo',
            cant_por_empaque: Number(p.cant_por_empaque || 1),
            precio_venta_empaque: Number(p.precio_venta_empaque || (p.precio_venta * (p.cant_por_empaque || 1))),
            presentacion: 'UNIDAD',
            tiene_variantes: false,
            variantes: [],
            producto_variante_id: null,
            stock: p.stock,
            cantidad: 1,
            es_perecedero: !!p.es_perecedero,
            lotes: p.lotes || [],
            lote_id: null
        });
    }
    render();
}
function setQty(key, val) {
    const it = cart.find(i => i.cartKey === key);
    if (!it) return;
    const factor = (it.tiene_empaque && it.presentacion === 'EMPAQUE') ? it.cant_por_empaque : 1;
    const maxCant = factor > 1 ? Math.floor(it.stock / factor) : it.stock;
    val = Math.max(1, Math.min(val, maxCant > 0 ? maxCant : 1));
    it.cantidad = val;
    render();
}
function setPres(key, presVal) {
    const it = cart.find(i => i.cartKey === key);
    if (!it) return;
    it.presentacion = presVal;
    render();
}
function setLote(key, loteId) {
    const it = cart.find(i => i.cartKey === key);
    if (!it) return;
    it.lote_id = loteId ? Number(loteId) : null;
}
function remove(key) { cart = cart.filter(i => i.cartKey !== key); render(); }

function render() {
    const box = document.getElementById('items');
    const empty = document.getElementById('empty');
    box.querySelectorAll('.cart-row').forEach(n => n.remove());

    if (cart.length === 0) {
        empty.style.display = 'block';
    } else {
        empty.style.display = 'none';
        cart.forEach(it => {
            const row = document.createElement('div');
            row.className = 'cart-row';
            
            const isEmpaque = it.tiene_empaque && it.presentacion === 'EMPAQUE';
            const pricePres = isEmpaque ? it.precio_venta_empaque : it.precio_venta;
            const lineTotal = pricePres * it.cantidad;

            let presHtml = '';
            if (it.tiene_empaque) {
                presHtml = `<div style="margin-top:4px;">
                    <select onchange="setPres('${it.cartKey}', this.value)" style="font-size:11px;padding:2px 4px;border-radius:4px;border:1px solid #0284c7;background:#f0f9ff;width:100%;">
                        <option value="UNIDAD" ${it.presentacion === 'UNIDAD' ? 'selected' : ''}>🧪 Unidad (${money(it.precio_venta)})</option>
                        <option value="EMPAQUE" ${it.presentacion === 'EMPAQUE' ? 'selected' : ''}>📦 ${it.nombre_empaque} x${it.cant_por_empaque} (${money(it.precio_venta_empaque)})</option>
                    </select>
                </div>`;
            }

            let loteHtml = '';
            if (it.es_perecedero && it.lotes && it.lotes.length > 0) {
                const options = it.lotes.map(l => {
                    const f = l.fecha_vencimiento ? l.fecha_vencimiento.substring(0, 10) : '';
                    const sel = (it.lote_id === l.id) ? 'selected' : '';
                    return `<option value="${l.id}" ${sel}>${l.codigo_lote} (vence ${f} - stock ${l.stock_actual})</option>`;
                }).join('');

                loteHtml = `<div style="margin-top:4px;">
                    <select onchange="setLote('${it.cartKey}', this.value)" style="font-size:11px;padding:2px 4px;border-radius:4px;border:1px solid #e67e22;background:#fff8f0;width:100%;">
                        <option value="">Auto FEFO (Más próximo a vencer)</option>
                        ${options}
                    </select>
                </div>`;
            }

            row.innerHTML = `
                <div class="info">
                    <div class="n">${it.nombre}</div>
                    <div class="p">${money(pricePres)} ${isEmpaque ? 'per ' + it.nombre_empaque : 'c/u'}</div>
                    ${presHtml}
                    ${loteHtml}
                </div>
                <div class="qty">
                    <button type="button" onclick="setQty('${it.cartKey}', ${it.cantidad-1})">−</button>
                    <input type="number" value="${it.cantidad}" min="1"
                           onchange="setQty('${it.cartKey}', parseInt(this.value)||1)">
                    <button type="button" onclick="setQty('${it.cartKey}', ${it.cantidad+1})">+</button>
                </div>
                <div class="line">${money(lineTotal)}</div>
                <button type="button" class="rm" onclick="remove('${it.cartKey}')"><i class="fa-solid fa-xmark"></i></button>`;
            box.appendChild(row);
        });
    }

    const subtotal = cart.reduce((s,i) => {
        const isEmp = i.tiene_empaque && i.presentacion === 'EMPAQUE';
        const p = isEmp ? i.precio_venta_empaque : i.precio_venta;
        return s + (p * i.cantidad);
    }, 0);

    let desc = Math.max(0, Number(document.getElementById('descuento').value) || 0);
    desc = Math.min(desc, subtotal);
    const total = subtotal - desc;
    const igv = IGV > 0 ? (total - (total / (1 + IGV))) : 0;

    document.getElementById('count').textContent = cart.reduce((s,i)=>s+i.cantidad,0);
    document.getElementById('t_sub').textContent = money(subtotal);
    document.getElementById('t_desc').textContent = money(desc);
    document.getElementById('t_igv').textContent = money(igv);
    document.getElementById('t_total').textContent = money(total);
    document.getElementById('btn_total').textContent = money(total);

    // Efectivo recibido / vuelto
    const esEfectivo = document.getElementById('metodo_pago').value === 'EFECTIVO';
    const efecStr = document.getElementById('efectivo').value;
    const efec = Number(efecStr) || 0;
    const vuelto = (esEfectivo && efecStr !== '' && efec >= total) ? (efec - total) : 0;
    document.getElementById('vuelto').textContent = money(vuelto);
    const efectivoInsuficiente = esEfectivo && efecStr !== '' && efec < total;

    document.getElementById('cobrar').disabled = cart.length === 0 || efectivoInsuficiente;
}
document.getElementById('descuento').addEventListener('input', render);
document.getElementById('efectivo').addEventListener('input', render);

/* ---- Cobrar ---- */
document.getElementById('cobrar').addEventListener('click', async () => {
    const btn = document.getElementById('cobrar');
    const msg = document.getElementById('msg');
    msg.className = 'pos-msg';
    btn.disabled = true;

    const metodoSel = document.getElementById('metodo_pago').value;
    const efecVal = document.getElementById('efectivo').value;
    const payload = {
        cliente_id: document.getElementById('cliente_id').value || null,
        tipo_comprobante: document.getElementById('tipo_comprobante').value,
        metodo_pago: metodoSel,
        descuento: Number(document.getElementById('descuento').value) || 0,
        efectivo_recibido: (metodoSel === 'EFECTIVO' && efecVal !== '') ? Number(efecVal) : null,
        items: cart.map(i => {
            const isEmp = i.tiene_empaque && i.presentacion === 'EMPAQUE';
            const factor = isEmp ? i.cant_por_empaque : 1;
            const pricePres = isEmp ? i.precio_venta_empaque : i.precio_venta;
            return {
                producto_id: i.id,
                producto_variante_id: i.producto_variante_id,
                cantidad: i.cantidad * factor,
                precio: factor > 0 ? (pricePres / factor) : pricePres,
                lote_id: i.lote_id
            };
        }),
    };

    try {
        const res = await fetch(URL_STORE, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok) {
            msg.textContent = data.message || 'No se pudo registrar la venta.';
            msg.className = 'pos-msg err';
            btn.disabled = false;
            return;
        }
        window.location = data.redirect;
    } catch (err) {
        msg.textContent = 'Error de conexión. Intenta de nuevo.';
        msg.className = 'pos-msg err';
        btn.disabled = false;
    }
});

/* Init */
toggleCash();
buscar('');
document.addEventListener('DOMContentLoaded', () => {
    initSearchableSelect('cliente_id', { placeholder: 'Buscar cliente...' });
});
</script>
@endpush
