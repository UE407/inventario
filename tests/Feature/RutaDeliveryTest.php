<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Ruta;
use App\Models\User;
use App\Services\Ventas\PedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutaDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_creacion_de_ruta_y_autocalculo_de_fecha_entrega_en_pedido(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Empresa Rutas GT',
            'ruc' => '1234567-9',
            'moneda' => 'Q',
            'estado_suscripcion' => 'activa',
        ]);

        $vendedor = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Vendedor Ruta',
            'email' => 'ruta_test@test.com',
            'password' => bcrypt('password'),
            'rol' => 'vendedor',
            'activo' => true,
        ]);

        $this->actingAs($vendedor);

        // Crear una Ruta de Entrega con 3 días estimados
        $ruta = Ruta::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Ruta 1 - Carretera a El Salvador',
            'codigo' => 'RUT-01',
            'dias_entrega_estimados' => 3,
            'activo' => true,
        ]);

        $this->assertEquals(3, $ruta->dias_entrega_estimados);

        // Crear un cliente asignado a dicha Ruta
        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'ruta_id' => $ruta->id,
            'nombre' => 'Supermercado Los Próceres',
            'numero_documento' => '1122334-5',
        ]);

        $this->assertEquals($ruta->id, $cliente->ruta_id);

        $prod = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'BEB002',
            'nombre' => 'Agua Salvavidas 1.5L',
            'unidad' => 'UND',
            'precio_compra' => 3.00,
            'precio_venta' => 5.00,
            'stock' => 100,
            'stock_minimo' => 10,
        ]);

        // Registrar pedido
        $service = new PedidoService();
        $pedido = $service->crearPedido([
            'cliente_id' => $cliente->id,
            'ruta_id' => $ruta->id,
            'fecha_entrega_estimada' => now()->addDays($ruta->dias_entrega_estimados)->format('Y-m-d'),
            'items' => [
                ['producto_id' => $prod->id, 'cantidad' => 10, 'precio' => 5.00]
            ],
        ]);

        $this->assertEquals($ruta->id, $pedido->ruta_id);
        $this->assertNotNull($pedido->fecha_pedido);
        $this->assertEquals(now()->addDays(3)->format('Y-m-d'), $pedido->fecha_entrega_estimada->format('Y-m-d'));
    }
}
