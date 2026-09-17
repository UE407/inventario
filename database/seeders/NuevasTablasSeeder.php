<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Lote;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Ruta;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeder para poblar las nuevas tablas:
 * - Rutas de entrega
 * - Lotes de vencimiento
 * - Variantes de producto (sabores/presentaciones)
 * - Pedidos de preventa/ruta
 *
 * Se puede ejecutar independientemente:
 *   php artisan db:seed --class=NuevasTablasSeeder
 */
class NuevasTablasSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = Empresa::all();

        if ($empresas->isEmpty()) {
            $this->command->error('No hay empresas registradas. Ejecuta primero los seeders iniciales.');
            return;
        }

        foreach ($empresas as $empresa) {
            Tenant::set($empresa->id);
            $this->command->info("Poblando nuevas tablas para la empresa: {$empresa->nombre}");

            $admin = User::where('empresa_id', $empresa->id)->first() ?? User::whereNull('empresa_id')->first();
            $productos = Producto::where('empresa_id', $empresa->id)->get();
            $clientes = Cliente::where('empresa_id', $empresa->id)->get();

            // ================================================================
            // 1) RUTAS DE ENTREGA (3)
            // ================================================================
            $rutasData = [
                ['Metropolitana Central', 'RUT-01', 1, 'Cubre Zona 1, 4, 9, 10 y 14 de Ciudad de Guatemala'],
                ['Occidente Express', 'RUT-02', 2, 'Chimaltenango, Sololá y Quetzaltenango'],
                ['Costa Sur', 'RUT-03', 3, 'Escuintla, Santa Lucía y Mazatenango'],
            ];

            $rutas = collect();
            foreach ($rutasData as [$nombre, $cod, $dias, $desc]) {
                $rutas->push(Ruta::firstOrCreate(
                    ['empresa_id' => $empresa->id, 'nombre' => $nombre],
                    [
                        'codigo' => $cod,
                        'dias_entrega_estimados' => $dias,
                        'descripcion' => $desc,
                        'activo' => true,
                    ]
                ));
            }

            // Asignar ruta a los clientes existentes
            if ($clientes->isNotEmpty() && $rutas->isNotEmpty()) {
                foreach ($clientes as $i => $cliente) {
                    $cliente->update(['ruta_id' => $rutas->random()->id]);
                }
            }

            // ================================================================
            // 2) LOTES DE PRODUCTOS PERECEDEROS
            // ================================================================
            if ($productos->isNotEmpty()) {
                // Marcar primeros 3 productos como perecederos
                $productosPerecederos = $productos->take(3);
                foreach ($productosPerecederos as $prod) {
                    $prod->update([
                        'es_perecedero' => true,
                        'dias_alerta_vencimiento' => 30,
                    ]);

                    // Lote 1: Por vencer (en 15 días)
                    Lote::firstOrCreate(
                        ['empresa_id' => $empresa->id, 'codigo_lote' => 'LOT-' . $prod->id . '-01'],
                        [
                            'producto_id' => $prod->id,
                            'fecha_vencimiento' => Carbon::today()->addDays(15),
                            'stock_inicial' => 50,
                            'stock_actual' => 30,
                            'estado' => 'POR_VENCER',
                        ]
                    );

                    // Lote 2: Vigente (en 60 días)
                    Lote::firstOrCreate(
                        ['empresa_id' => $empresa->id, 'codigo_lote' => 'LOT-' . $prod->id . '-02'],
                        [
                            'producto_id' => $prod->id,
                            'fecha_vencimiento' => Carbon::today()->addDays(60),
                            'stock_inicial' => 100,
                            'stock_actual' => 85,
                            'estado' => 'VIGENTE',
                        ]
                    );

                    // Lote 3: Vencido (hace 5 días)
                    Lote::firstOrCreate(
                        ['empresa_id' => $empresa->id, 'codigo_lote' => 'LOT-' . $prod->id . '-03'],
                        [
                            'producto_id' => $prod->id,
                            'fecha_vencimiento' => Carbon::today()->subDays(5),
                            'stock_inicial' => 20,
                            'stock_actual' => 10,
                            'estado' => 'VENCIDO',
                        ]
                    );
                }

                // ================================================================
                // 3) PRODUCTO VARIANTES (Sabores / Presentaciones)
                // ================================================================
                $productosConSabores = $productos->skip(3)->take(2);
                $saboresPool = ['Fresa 500ml', 'Vainilla 500ml', 'Chocolate 500ml', 'Naranja 500ml'];

                foreach ($productosConSabores as $prod) {
                    $prod->update(['tiene_variantes' => true]);

                    foreach ($saboresPool as $idx => $sabor) {
                        ProductoVariante::firstOrCreate(
                            ['empresa_id' => $empresa->id, 'producto_id' => $prod->id, 'sabor' => $sabor],
                            [
                                'codigo' => $prod->codigo . '-VAR' . ($idx + 1),
                                'stock' => rand(15, 60),
                                'stock_minimo' => 5,
                                'activo' => true,
                            ]
                        );
                    }
                }

                // ================================================================
                // 4) PEDIDOS DE PREVENTA (3)
                // ================================================================
                if ($clientes->isNotEmpty()) {
                    $estadosPedido = ['PENDIENTE', 'APROBADO', 'CONVERTIDO'];

                    foreach ($estadosPedido as $idx => $estado) {
                        $cliente = $clientes->random();
                        $fechaPedido = Carbon::now()->subDays(rand(1, 5));

                        $pedido = Pedido::create([
                            'empresa_id' => $empresa->id,
                            'numero' => 'PED-' . str_pad($idx + 1, 6, '0', STR_PAD_LEFT),
                            'cliente_id' => $cliente->id,
                            'user_id' => $admin?->id ?? 1,
                            'fecha_pedido' => $fechaPedido,
                            'fecha_entrega_estimada' => $fechaPedido->copy()->addDays(2),
                            'subtotal' => 0,
                            'impuesto' => 0,
                            'total' => 0,
                            'estado' => $estado,
                            'observacion' => "Pedido de prueba {$estado}",
                        ]);

                        $subtotal = 0;
                        $items = $productos->random(rand(2, 3));
                        foreach ($items as $itemProd) {
                            $cant = rand(2, 10);
                            $precio = $itemProd->precio_venta;
                            $sub = $cant * $precio;
                            $subtotal += $sub;

                            PedidoDetalle::create([
                                'empresa_id' => $empresa->id,
                                'pedido_id' => $pedido->id,
                                'producto_id' => $itemProd->id,
                                'cantidad' => $cant,
                                'precio' => $precio,
                                'subtotal' => $sub,
                            ]);
                        }

                        $impuesto = round($subtotal * 0.12, 2);
                        $pedido->update([
                            'subtotal' => $subtotal,
                            'impuesto' => $impuesto,
                            'total' => $subtotal + $impuesto,
                        ]);
                    }
                }
            }

            Tenant::clear();
        }

        $this->command->info('¡Nuevas tablas pobladas con éxito (Rutas, Lotes, Variantes y Pedidos)!');
    }
}
