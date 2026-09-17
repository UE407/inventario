<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentasEmpaqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_venta_y_pedido_con_fardos_descuentan_unidades_base_correctamente(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Distribuidora Test GT',
            'ruc' => '1234567-8',
            'moneda' => 'Q',
            'estado_suscripcion' => 'activa',
            'suscripcion_termina_en' => now()->addYear(),
        ]);

        $user = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Vendedor Fardos',
            'email' => 'vendedor@test.com',
            'password' => bcrypt('password'),
            'rol' => 'admin',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Tienda El Rosario',
            'tipo_documento' => 'NIT',
            'numero_documento' => '5544332-1',
            'activo' => true,
        ]);

        // Producto: Aceite Vegetal 1L (1 Fardo = 12 unidades, Q150.00 por fardo)
        $prod = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'ACE-FARDO-12',
            'nombre' => 'Aceite Vegetal 1L',
            'unidad' => 'Unidad',
            'precio_compra' => 10.00,
            'precio_venta' => 16.50,
            'stock' => 120, // 120 unidades en stock (= 10 fardos)
            'stock_minimo' => 12,
            'tiene_empaque' => true,
            'nombre_empaque' => 'Fardo',
            'cant_por_empaque' => 12,
            'precio_venta_empaque' => 150.00,
            'activo' => true,
        ]);

        $this->actingAs($user);
        session(['empresa_id' => $empresa->id]);
        \App\Support\Tenant::set($empresa->id);

        // 1. Probar Venta Directa de 2 Fardos (2 * 12 = 24 unidades base, a Q12.50 por unidad = Q300 total)
        $resVenta = $this->postJson(route('ventas.store'), [
            'cliente_id' => $cliente->id,
            'tipo_comprobante' => 'TICKET',
            'metodo_pago' => 'EFECTIVO',
            'efectivo_recibido' => 300.00,
            'descuento' => 0,
            'items' => [
                [
                    'producto_id' => $prod->id,
                    'cantidad' => 24, // 2 fardos convertidos a unidades base
                    'precio' => 12.50, // Q150 / 12 = Q12.50 por unidad base
                    'lote_id' => null,
                ]
            ]
        ]);

        if ($resVenta->status() === 422) {
            fwrite(STDERR, "422 Content: " . $resVenta->content() . "\n");
        }
        $resVenta->assertStatus(200);
        $resVenta->assertJsonStructure(['redirect']);

        // Stock debe haber bajado de 120 a 96 (120 - 24 = 96)
        $this->assertEquals(96, $prod->fresh()->stock);

        // 2. Probar Toma de Pedido en Ruta de 1 Fardo (1 * 12 = 12 unidades base, a Q12.50 por unidad)
        $resPedido = $this->postJson(route('pedidos.store'), [
            'cliente_id' => $cliente->id,
            'observacion' => 'Entrega 1 Fardo de Aceite',
            'items' => [
                [
                    'producto_id' => $prod->id,
                    'cantidad' => 12, // 1 fardo
                    'precio' => 12.50,
                    'lote_id' => null,
                ]
            ]
        ]);

        $resPedido->assertStatus(200);
        $resPedido->assertJsonStructure(['redirect']);

        // Stock debe haber bajado de 96 a 84 (reserva de stock al registrar pedido)
        $this->assertEquals(84, $prod->fresh()->stock);
    }
}
