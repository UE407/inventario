<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use App\Services\Ventas\PedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpcionesConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_empresa_sin_impuestos_calcula_iva_cero(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Negocio Exento GT',
            'ruc' => '1234567-9',
            'moneda' => 'Q',
            'igv' => 0.00, // Sin impuestos
        ]);

        $this->assertEquals(0.00, $empresa->tasaIgv());

        $vendedor = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Vendedor Exento',
            'email' => 'exento@test.com',
            'password' => bcrypt('password'),
            'rol' => 'admin',
            'activo' => true,
        ]);

        $this->actingAs($vendedor);

        $prod = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'EX001',
            'nombre' => 'Producto Sin IVA',
            'unidad' => 'UND',
            'precio_compra' => 10.00,
            'precio_venta' => 15.00,
            'stock' => 50,
            'stock_minimo' => 5,
        ]);

        $service = new PedidoService();
        $pedido = $service->crearPedido([
            'items' => [
                ['producto_id' => $prod->id, 'cantidad' => 2, 'precio' => 15.00]
            ]
        ]);

        $this->assertEquals(30.00, $pedido->subtotal);
        $this->assertEquals(0.00, $pedido->impuesto);
        $this->assertEquals(30.00, $pedido->total);
    }

    public function test_empresa_con_iva_guatemala_12_calcula_iva_incluido_en_precio(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Negocio General GT',
            'ruc' => '9876543-2',
            'moneda' => 'Q',
            'igv' => 12.00, // IVA 12% Ley de Guatemala
        ]);

        $this->assertEquals(0.12, $empresa->tasaIgv());

        $vendedor = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Vendedor GT',
            'email' => 'gt@test.com',
            'password' => bcrypt('password'),
            'rol' => 'admin',
            'activo' => true,
        ]);

        $this->actingAs($vendedor);

        $prod = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'IVA001',
            'nombre' => 'Producto con IVA',
            'unidad' => 'UND',
            'precio_compra' => 50.00,
            'precio_venta' => 112.00, // Precio de venta al público Q112.00 (ya incluye Q12 de IVA)
            'stock' => 10,
            'stock_minimo' => 2,
        ]);

        $service = new PedidoService();
        $pedido = $service->crearPedido([
            'items' => [
                ['producto_id' => $prod->id, 'cantidad' => 1, 'precio' => 112.00]
            ]
        ]);

        $this->assertEquals(112.00, $pedido->subtotal);
        $this->assertEquals(12.00, $pedido->impuesto); // IVA 12% incluido (112 - 100)
        $this->assertEquals(112.00, $pedido->total); // Total exacto a pagar
    }
}
