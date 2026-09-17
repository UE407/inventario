<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoSaboresTest extends TestCase
{
    use RefreshDatabase;

    public function test_creacion_de_producto_con_sabores_y_variantes(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Embotelladora Guatemala',
            'ruc' => '4433221-9',
            'moneda' => 'Q',
            'estado_suscripcion' => 'activa',
            'suscripcion_termina_en' => now()->addYear(),
        ]);

        $admin = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Admin Sabores',
            'email' => 'sabores@test.com',
            'password' => bcrypt('password'),
            'rol' => 'admin',
            'activo' => true,
        ]);

        $this->actingAs($admin);
        session(['empresa_id' => $empresa->id]);
        \App\Support\Tenant::set($empresa->id);

        $res = $this->post(route('productos.store'), [
            'codigo' => 'BEB-KERNS-15L',
            'nombre' => "Jugo Kern's 1.5L",
            'unidad' => 'UND',
            'precio_compra' => 8.00,
            'precio_venta' => 12.50,
            'stock' => 0,
            'stock_minimo' => 10,
            'tiene_variantes' => 1,
            'variantes' => [
                [
                    'sabor' => 'Manzana',
                    'codigo' => 'KERNS-MANZ-15L',
                    'stock' => 50,
                    'stock_minimo' => 5,
                ],
                [
                    'sabor' => 'Melocotón',
                    'codigo' => 'KERNS-MEL-15L',
                    'stock' => 30,
                    'stock_minimo' => 5,
                ],
                [
                    'sabor' => 'Pera',
                    'codigo' => 'KERNS-PERA-15L',
                    'stock' => 20,
                    'stock_minimo' => 5,
                ],
            ]
        ]);

        $res->assertRedirect(route('productos.index'));

        $prod = Producto::where('codigo', 'BEB-KERNS-15L')->first();
        $this->assertNotNull($prod);
        $this->assertTrue($prod->tiene_variantes);
        $this->assertEquals(100, $prod->stock); // 50 + 30 + 20 = 100

        $this->assertCount(3, $prod->variantes);
        $this->assertDatabaseHas('producto_variantes', [
            'producto_id' => $prod->id,
            'sabor' => 'Manzana',
            'stock' => 50,
        ]);
        $this->assertDatabaseHas('producto_variantes', [
            'producto_id' => $prod->id,
            'sabor' => 'Melocotón',
            'stock' => 30,
        ]);
        $this->assertDatabaseHas('producto_variantes', [
            'producto_id' => $prod->id,
            'sabor' => 'Pera',
            'stock' => 20,
        ]);
    }
}
