<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Lote;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Services\Ventas\PedidoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_crea_pedido_en_ruta_y_reserva_stock_inmediatamente(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Distribuidora GT',
            'ruc' => '1234567-9',
            'moneda' => 'Q',
        ]);

        $vendedor = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Vendedor En Ruta',
            'email' => 'ruta@saas.test',
            'password' => bcrypt('password'),
            'rol' => 'vendedor',
            'activo' => true,
        ]);

        $this->actingAs($vendedor);

        $prod = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'BEB001',
            'nombre' => 'Jugo Kerns 1.5L',
            'unidad' => 'UND',
            'precio_compra' => 8.00,
            'precio_venta' => 12.50,
            'stock' => 20,
            'stock_minimo' => 5,
            'es_perecedero' => true,
            'dias_alerta_vencimiento' => 30,
        ]);

        $lote = Lote::create([
            'empresa_id' => $empresa->id,
            'producto_id' => $prod->id,
            'codigo_lote' => 'LOTE-RUTA-01',
            'fecha_vencimiento' => Carbon::today()->addDays(15),
            'stock_inicial' => 20,
            'stock_actual' => 20,
            'estado' => 'VIGENTE',
        ]);

        $service = new PedidoService();
        $pedido = $service->crearPedido([
            'items' => [
                [
                    'producto_id' => $prod->id,
                    'cantidad' => 5,
                    'precio' => 12.50,
                    'lote_id' => $lote->id,
                ]
            ],
            'observacion' => 'Entrega mañana por la mañana',
        ]);

        $this->assertEquals('PENDIENTE', $pedido->estado);
        $this->assertEquals(15, $prod->fresh()->stock); // Stock del producto reservado de 20 a 15
        $this->assertEquals(15, $lote->fresh()->stock_actual); // Stock del lote reservado de 20 a 15

        // Convertir pedido a Venta
        $venta = $service->convertirAVenta($pedido, [
            'tipo_comprobante' => 'FACTURA',
            'metodo_pago' => 'EFECTIVO',
            'efectivo_recibido' => 100.00,
        ]);

        $this->assertEquals('CONVERTIDO', $pedido->fresh()->estado);
        $this->assertEquals('COMPLETADA', $venta->estado);
        $this->assertEquals($venta->id, $pedido->fresh()->venta_id);
    }

    public function test_aislamiento_de_pedidos_por_vendedor(): void
    {
        $empresa = Empresa::create(['nombre' => 'Empresa GT', 'ruc' => '1234567-9', 'moneda' => 'Q']);

        $vendedor1 = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Vendedor 1',
            'email' => 'v1@test.com',
            'password' => bcrypt('password'),
            'rol' => 'vendedor',
            'activo' => true,
        ]);

        $vendedor2 = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Vendedor 2',
            'email' => 'v2@test.com',
            'password' => bcrypt('password'),
            'rol' => 'vendedor',
            'activo' => true,
        ]);

        Pedido::create([
            'empresa_id' => $empresa->id,
            'numero' => 'PED-000001',
            'user_id' => $vendedor1->id,
            'fecha_pedido' => now(),
            'total' => 100,
            'estado' => 'PENDIENTE',
        ]);

        Pedido::create([
            'empresa_id' => $empresa->id,
            'numero' => 'PED-000002',
            'user_id' => $vendedor2->id,
            'fecha_pedido' => now(),
            'total' => 200,
            'estado' => 'PENDIENTE',
        ]);

        // Vendedor 1 sólo debe ver su propio pedido
        $visibleV1 = Pedido::visiblesParaUsuario($vendedor1)->get();
        $this->assertCount(1, $visibleV1);
        $this->assertEquals('PED-000001', $visibleV1->first()->numero);
    }

    public function test_busqueda_de_pedidos_por_nombre_o_nit_de_cliente(): void
    {
        $empresa = Empresa::create(['nombre' => 'Empresa Busqueda GT', 'ruc' => '1234567-9', 'moneda' => 'Q', 'estado_suscripcion' => 'activa']);
        $admin = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Admin GT',
            'email' => 'admin_search@test.com',
            'password' => bcrypt('password'),
            'rol' => 'admin',
            'activo' => true,
        ]);
        $this->actingAs($admin);

        $cliente1 = \App\Models\Cliente::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Supermercado El Sol',
            'numero_documento' => '1029384-5',
        ]);

        $cliente2 = \App\Models\Cliente::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Tienda La Bendicion',
            'numero_documento' => '5544332-1',
        ]);

        Pedido::create([
            'empresa_id' => $empresa->id,
            'numero' => 'PED-000100',
            'cliente_id' => $cliente1->id,
            'user_id' => $admin->id,
            'fecha_pedido' => now(),
            'total' => 500,
            'estado' => 'PENDIENTE',
        ]);

        Pedido::create([
            'empresa_id' => $empresa->id,
            'numero' => 'PED-000101',
            'cliente_id' => $cliente2->id,
            'user_id' => $admin->id,
            'fecha_pedido' => now(),
            'total' => 300,
            'estado' => 'PENDIENTE',
        ]);

        // Búsqueda por nombre de cliente
        $resNombre = $this->get(route('pedidos.index', ['q' => 'Supermercado']));
        $resNombre->assertStatus(200);
        $resNombre->assertSee('PED-000100');
        $resNombre->assertDontSee('PED-000101');

        // Búsqueda por NIT de cliente
        $resNit = $this->get(route('pedidos.index', ['q' => '5544332']));
        $resNit->assertStatus(200);
        $resNit->assertSee('PED-000101');
        $resNit->assertDontSee('PED-000100');
    }
}
