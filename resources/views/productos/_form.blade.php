<div class="form-grid">
    <div class="form-group">
        <label>Código <span style="color:#e74c3c">*</span></label>
        <input type="text" name="codigo" class="form-control @error('codigo') invalid @enderror"
               value="{{ old('codigo', $producto->codigo) }}" placeholder="P0001" required>
        @error('codigo') <div class="invalid-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Unidad <span style="color:#e74c3c">*</span></label>
        <select name="unidad" class="form-control">
            @foreach(['UND'=>'Unidad','KG'=>'Kilogramo','LT'=>'Litro','CAJA'=>'Caja','PAQ'=>'Paquete','DOC'=>'Docena'] as $val=>$txt)
                <option value="{{ $val }}" {{ old('unidad', $producto->unidad) === $val ? 'selected' : '' }}>{{ $txt }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group full">
        <label>Nombre <span style="color:#e74c3c">*</span></label>
        <input type="text" name="nombre" class="form-control @error('nombre') invalid @enderror"
               value="{{ old('nombre', $producto->nombre) }}" required>
        @error('nombre') <div class="invalid-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Categoría</label>
        <select name="categoria_id" class="form-control">
            <option value="">— Sin categoría —</option>
            @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" {{ (string)old('categoria_id', $producto->categoria_id) === (string)$cat->id ? 'selected' : '' }}>
                    {{ $cat->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Marca</label>
        <select name="marca_id" class="form-control">
            <option value="">— Sin marca —</option>
            @foreach($marcas as $marca)
                <option value="{{ $marca->id }}" {{ (string)old('marca_id', $producto->marca_id) === (string)$marca->id ? 'selected' : '' }}>
                    {{ $marca->nombre }}
                </option>
            @endforeach
        </select>
    </div>

@php
    $simMoneda = \App\Models\Empresa::actual()->moneda ?? 'Q';
@endphp

    <div class="form-group">
        <label>Precio de compra ({{ $simMoneda }}) <span style="color:#e74c3c">*</span></label>
        <input type="number" step="0.01" min="0" name="precio_compra"
               class="form-control @error('precio_compra') invalid @enderror"
               value="{{ old('precio_compra', $producto->precio_compra ?? 0) }}" required>
        @error('precio_compra') <div class="invalid-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Precio de venta ({{ $simMoneda }}) <span style="color:#e74c3c">*</span></label>
        <input type="number" step="0.01" min="0" name="precio_venta"
               class="form-control @error('precio_venta') invalid @enderror"
               value="{{ old('precio_venta', $producto->precio_venta ?? 0) }}" required>
        @error('precio_venta') <div class="invalid-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Stock actual <span style="color:#e74c3c">*</span></label>
        <input type="number" min="0" name="stock"
               class="form-control @error('stock') invalid @enderror"
               value="{{ old('stock', $producto->stock ?? 0) }}" required>
        @error('stock') <div class="invalid-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Stock mínimo <span style="color:#e74c3c">*</span></label>
        <input type="number" min="0" name="stock_minimo"
               class="form-control @error('stock_minimo') invalid @enderror"
               value="{{ old('stock_minimo', $producto->stock_minimo ?? 5) }}" required>
        <div class="help">Se avisará cuando el stock llegue a este valor.</div>
    </div>

    <div class="form-group full">
        <label>Descripción</label>
        <textarea name="descripcion" class="form-control" placeholder="Opcional">{{ old('descripcion', $producto->descripcion) }}</textarea>
    </div>

    <div class="form-group full">
        <label>Imagen del producto</label>
        @if($producto->imagen ?? false)
            <div style="margin-bottom:8px">
                <img src="{{ asset('storage/'.$producto->imagen) }}" class="thumb" style="width:64px;height:64px" alt="">
            </div>
        @endif
        <input type="file" name="imagen" accept="image/*" class="form-control @error('imagen') invalid @enderror">
        <div class="help">JPG o PNG, máx 2 MB. Requiere haber ejecutado <code>php artisan storage:link</code>.</div>
        @error('imagen') <div class="invalid-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group full" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 10px;">
        <div class="checkbox-row" style="margin-bottom: 10px;">
            <input type="checkbox" name="es_perecedero" id="es_perecedero" value="1"
                   {{ old('es_perecedero', $producto->es_perecedero ?? false) ? 'checked' : '' }}>
            <label for="es_perecedero" style="margin:0; font-weight: 600; color: #1e293b;">
                <i class="fa-solid fa-calendar-xmark" style="color:#e67e22"></i> Producto Perecedero (Control de Lotes y Vencimientos FEFO)
            </label>
        </div>
        <div class="help" style="margin-bottom: 8px;">Si se marca, el sistema solicitará número de lote y fecha de vencimiento al comprar y descontará automáticamente por vencimiento más próximo en el POS.</div>

        <div class="form-group" style="margin: 0; max-width: 300px;">
            <label style="font-size: 0.85rem;">Días previos para alerta de vencimiento</label>
            <input type="number" min="1" max="365" name="dias_alerta_vencimiento" class="form-control"
                   value="{{ old('dias_alerta_vencimiento', $producto->dias_alerta_vencimiento ?? 30) }}">
        </div>
    </div>

    <div class="form-group full" style="background: #f0f9ff; padding: 15px; border-radius: 8px; border: 1px solid #bae6fd; margin-top: 10px;">
        <div class="checkbox-row" style="margin-bottom: 10px;">
            <input type="checkbox" name="tiene_empaque" id="tiene_empaque" value="1"
                   onchange="document.getElementById('sec_empaque').style.display = this.checked ? 'grid' : 'none';"
                   {{ old('tiene_empaque', $producto->tiene_empaque ?? false) ? 'checked' : '' }}>
            <label for="tiene_empaque" style="margin:0; font-weight: 600; color: #0369a1;">
                <i class="fa-solid fa-boxes-packing" style="color:#0284c7"></i> Venta / Compra por Fardo o Caja (Conversión de Empaque)
            </label>
        </div>
        <div class="help" style="margin-bottom: 10px; color:#0369a1;">
            Permite comprar o vender este producto por Fardo/Caja y convertirlos automáticamente a la unidad mínima en bodega.
        </div>

        <div id="sec_empaque" style="display: {{ old('tiene_empaque', $producto->tiene_empaque ?? false) ? 'grid' : 'none' }}; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
            <div>
                <label style="font-size: 0.85rem; font-weight: 600;">Nombre del Empaque</label>
                <input type="text" name="nombre_empaque" class="form-control" placeholder="Ej: Fardo, Caja, Paquete"
                       value="{{ old('nombre_empaque', $producto->nombre_empaque ?? 'Fardo') }}">
            </div>
            <div>
                <label style="font-size: 0.85rem; font-weight: 600;">Unidades por Empaque</label>
                <input type="number" min="2" name="cant_por_empaque" class="form-control" placeholder="Ej: 12, 24"
                       value="{{ old('cant_por_empaque', $producto->cant_por_empaque ?? 12) }}">
                <div class="help">Cantidad de unidades contenidas en 1 fardo/caja.</div>
            </div>
            <div>
                <label style="font-size: 0.85rem; font-weight: 600;">Precio Venta por Empaque ({{ $simMoneda }})</label>
                <input type="number" step="0.01" min="0" name="precio_venta_empaque" class="form-control" placeholder="0.00"
                       value="{{ old('precio_venta_empaque', $producto->precio_venta_empaque) }}">
                <div class="help">Precio de venta por fardo o caja completo.</div>
            </div>
        </div>
    </div>

    <div class="form-group full" style="background: #fefce8; padding: 15px; border-radius: 8px; border: 1px solid #fef08a; margin-top: 10px;">
        <div class="checkbox-row" style="margin-bottom: 10px;">
            <input type="checkbox" name="tiene_variantes" id="tiene_variantes" value="1"
                   onchange="document.getElementById('sec_variantes').style.display = this.checked ? 'block' : 'none';"
                   {{ old('tiene_variantes', $producto->tiene_variantes ?? false) ? 'checked' : '' }}>
            <label for="tiene_variantes" style="margin:0; font-weight: 600; color: #854d0e;">
                <i class="fa-solid fa-layer-group" style="color:#ca8a04"></i> Sabores o Variantes de Producto (Ej: Manzana, Melocotón, Uva)
            </label>
        </div>
        <div class="help" style="margin-bottom: 10px; color:#854d0e;">
            Si el producto tiene múltiples sabores o colores, agrega cada variante aquí. Cada sabor tendrá su propio stock independiente en bodega.
        </div>

        <div id="sec_variantes" style="display: {{ old('tiene_variantes', $producto->tiene_variantes ?? false) ? 'block' : 'none' }}; margin-top: 12px;">
            <table class="table" style="width:100%; background:#fff; border-radius:6px; overflow:hidden;" id="tablaVariantes">
                <thead>
                    <tr style="background:#fef9c3; color:#713f12; text-align:left; font-size:12px;">
                        <th style="padding:8px 12px;">Sabor / Variante <span style="color:#e74c3c">*</span></th>
                        <th style="padding:8px 12px; width:160px;">Código SKU (Opcional)</th>
                        <th style="padding:8px 12px; width:110px;">Stock Inicial</th>
                        <th style="padding:8px 12px; width:110px;">Stock Mín.</th>
                        <th style="padding:8px 12px; width:40px;"></th>
                    </tr>
                </thead>
                <tbody id="listaVariantes">
                    @php
                        $variantesList = old('variantes', $producto->variantes ?? []);
                    @endphp
                    @forelse($variantesList as $index => $v)
                        <tr>
                            <td style="padding:6px;">
                                <input type="hidden" name="variantes[{{ $index }}][id]" value="{{ is_object($v) ? $v->id : ($v['id'] ?? '') }}">
                                <input type="text" name="variantes[{{ $index }}][sabor]" class="form-control" placeholder="Ej: Manzana, Limón, Barbacoa" value="{{ is_object($v) ? $v->sabor : ($v['sabor'] ?? '') }}" required>
                            </td>
                            <td style="padding:6px;">
                                <input type="text" name="variantes[{{ $index }}][codigo]" class="form-control" placeholder="SKU o Código" value="{{ is_object($v) ? $v->codigo : ($v['codigo'] ?? '') }}">
                            </td>
                            <td style="padding:6px;">
                                <input type="number" min="0" name="variantes[{{ $index }}][stock]" class="form-control" value="{{ is_object($v) ? $v->stock : ($v['stock'] ?? 0) }}">
                            </td>
                            <td style="padding:6px;">
                                <input type="number" min="0" name="variantes[{{ $index }}][stock_minimo]" class="form-control" value="{{ is_object($v) ? $v->stock_minimo : ($v['stock_minimo'] ?? 5) }}">
                            </td>
                            <td style="padding:6px; text-align:center;">
                                <button type="button" class="btn-icon del" onclick="this.closest('tr').remove();"><i class="fa-solid fa-xmark"></i></button>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
            <button type="button" class="btn btn-light btn-sm" style="margin-top:8px;" onclick="agregarFilaSabor()">
                <i class="fa-solid fa-plus" style="color:#ca8a04"></i> Agregar Sabor
            </button>
        </div>
    </div>

    <div class="form-group full">
        <div class="checkbox-row" style="margin:0">
            <input type="checkbox" name="activo" id="activo" value="1"
                   {{ old('activo', $producto->activo ?? true) ? 'checked' : '' }}>
            <label for="activo" style="margin:0">Producto activo (visible en ventas)</label>
        </div>
    </div>
</div>

<div class="form-actions">
    <button type="submit" class="btn btn-primary" style="width:auto">
        <i class="fa-solid fa-floppy-disk"></i> Guardar
    </button>
    <a href="{{ route('productos.index') }}" class="btn btn-light">Cancelar</a>
</div>

<script>
let idxVar = {{ count($variantesList ?? []) }};
function agregarFilaSabor() {
    const i = idxVar++;
    const tbody = document.getElementById('listaVariantes');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="padding:6px;">
            <input type="text" name="variantes[${i}][sabor]" class="form-control" placeholder="Ej: Manzana, Limón, Barbacoa" required>
        </td>
        <td style="padding:6px;">
            <input type="text" name="variantes[${i}][codigo]" class="form-control" placeholder="SKU o Código">
        </td>
        <td style="padding:6px;">
            <input type="number" min="0" name="variantes[${i}][stock]" class="form-control" value="0">
        </td>
        <td style="padding:6px;">
            <input type="number" min="0" name="variantes[${i}][stock_minimo]" class="form-control" value="5">
        </td>
        <td style="padding:6px; text-align:center;">
            <button type="button" class="btn-icon del" onclick="this.closest('tr').remove();"><i class="fa-solid fa-xmark"></i></button>
        </td>`;
    tbody.appendChild(tr);
}
</script>
