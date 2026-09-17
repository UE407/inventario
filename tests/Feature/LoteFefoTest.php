<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Lote;
use App\Models\Producto;
use App\Services\Inventario\LoteService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoteFefoTest extends TestCase
{
    use RefreshDatabase;

    public function test_descuenta_stock_siguiendo_fefo_primero_lote_mas_proximo_a_vencer(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Test Tenant GT',
            'nit' => '1234567-9',
            'moneda' => 'Q',
        ]);

        $prod = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'PER001',
            'nombre' => 'Leche Perecedera 1L',
            'unidad' => 'UND',
            'precio_compra' => 5.00,
            'precio_venta' => 8.00,
            'stock' => 15,
            'stock_minimo' => 2,
            'es_perecedero' => true,
            'dias_alerta_vencimiento' => 30,
        ]);

        $lote1 = Lote::create([
            'empresa_id' => $empresa->id,
            'producto_id' => $prod->id,
            'codigo_lote' => 'LOTE-MAS-PROXIMO',
            'fecha_vencimiento' => Carbon::today()->addDays(5),
            'stock_inicial' => 5,
            'stock_actual' => 5,
            'estado' => 'VIGENTE',
        ]);

        $lote2 = Lote::create([
            'empresa_id' => $empresa->id,
            'producto_id' => $prod->id,
            'codigo_lote' => 'LOTE-LEJANO',
            'fecha_vencimiento' => Carbon::today()->addDays(40),
            'stock_inicial' => 10,
            'stock_actual' => 10,
            'estado' => 'VIGENTE',
        ]);

        $service = new LoteService();
        $descuentos = $service->descontarStockFEFO($prod, 7);

        $this->assertCount(2, $descuentos);
        
        // El primer lote consumido debe ser LOTE-MAS-PROXIMO (5 unidades)
        $this->assertEquals($lote1->id, $descuentos[0]['lote']->id);
        $this->assertEquals(5, $descuentos[0]['cantidad']);

        // El segundo lote consumido debe ser LOTE-LEJANO (2 unidades)
        $this->assertEquals($lote2->id, $descuentos[1]['lote']->id);
        $this->assertEquals(2, $descuentos[1]['cantidad']);

        // Lote 1 debe quedar en 0 (AGOTADO) y Lote 2 en 8 (VIGENTE)
        $this->assertEquals(0, $lote1->fresh()->stock_actual);
        $this->assertEquals('AGOTADO', $lote1->fresh()->estado);
        $this->assertEquals(8, $lote2->fresh()->stock_actual);
    }
}
