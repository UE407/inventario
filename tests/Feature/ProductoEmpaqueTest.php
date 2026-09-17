<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use App\Services\Ventas\PedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoEmpaqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_producto_con_configuracion_de_empaque_fardo(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Empresa Fardos GT',
            'ruc' => '9988776-5',
            'moneda' => 'Q',
        ]);

        $admin = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Admin Fardos',
            'email' => 'fardos@test.com',
            'password' => bcrypt('password'),
            'rol' => 'admin',
            'activo' => true,
        ]);

        $this->actingAs($admin);

        // Crear producto con empaque fardo (1 fardo = 12 unidades)
        $prod = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'BEB-FARDO-01',
            'nombre' => 'Agua Salvavidas 600ml',
            'unidad' => 'UND',
            'precio_compra' => 2.50,
            'precio_venta' => 4.00,
            'stock' => 120, // 120 unidades en bodega = 10 fardos
            'stock_minimo' => 12,
            'tiene_empaque' => true,
            'nombre_empaque' => 'Fardo',
            'cant_por_empaque' => 12,
            'precio_venta_empaque' => 40.00,
        ]);

        $this->assertTrue($prod->tiene_empaque);
        $this->assertEquals(12, $prod->cant_por_empaque);
        $this->assertEquals(40.00, $prod->precio_venta_empaque);

        // Pedido de 24 unidades (2 fardos de 12)
        $service = new PedidoService();
        $pedido = $service->crearPedido([
            'items' => [
                [
                    'producto_id' => $prod->id,
                    'cantidad' => 24, // 2 fardos convertidos a 24 unidades base
                    'precio' => 3.33,
                ]
            ]
        ]);

        $this->assertEquals('PENDIENTE', $pedido->estado);
        $this->assertEquals(96, $prod->fresh()->stock); // 120 - 24 = 96 unidades en bodega
    }
}
