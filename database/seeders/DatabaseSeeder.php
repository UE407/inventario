<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Marca;
use App\Models\Plan;
use App\Models\PlataformaConfig;
use App\Models\Suscripcion;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Support\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ===== Configuración global de la plataforma =====
        PlataformaConfig::create([
            'nombre_saas' => 'SaaS Ventas e Inventario',
            'dias_trial' => 14,
            'correo_soporte' => 'soporte@saas.test',
            'moneda' => 'Q',
        ]);

        // ===== Planes (catálogo de suscripción) =====
        $planGratis = Plan::create([
            'nombre' => 'Gratis', 'slug' => 'gratis', 'precio' => 0,
            'limite_productos' => 20, 'limite_usuarios' => 1, 'limite_ventas_mes' => 100,
            'descripcion' => 'Para empezar y probar el sistema.', 'orden' => 1,
        ]);
        $planEmprendedor = Plan::create([
            'nombre' => 'Emprendedor', 'slug' => 'emprendedor', 'precio' => 350.00,
            'limite_productos' => 500, 'limite_usuarios' => 5, 'limite_ventas_mes' => 3000,
            'descripcion' => 'Para negocios en crecimiento.', 'orden' => 2,
        ]);
        $planNegocio = Plan::create([
            'nombre' => 'Negocio', 'slug' => 'negocio', 'precio' => 750.00,
            'limite_productos' => null, 'limite_usuarios' => null, 'limite_ventas_mes' => null,
            'descripcion' => 'Todo ilimitado para operaciones grandes.', 'orden' => 3,
        ]);

        // ===== Super administrador de la plataforma (sin empresa) =====
        User::create([
            'empresa_id' => null,
            'name' => 'Super Admin',
            'email' => 'super@saas.test',
            'password' => 'password',
            'rol' => 'admin',
            'is_super' => true,
            'activo' => true,
        ]);

        // ===== Empresa / Tenant #1 =====
        $empresa = Empresa::create([
            'nombre' => 'Mi Negocio Guatemala',
            'ruc' => '1234567-9',
            'direccion' => 'Av. Reforma 10-15, Zona 10, Guatemala',
            'telefono' => '2345-6789',
            'email' => 'ventas@minegocio.test',
            'moneda' => 'Q',
            'igv' => 12.00,
            'plan_id' => $planNegocio->id,
            'estado_suscripcion' => 'activa',
            'suscripcion_termina_en' => Carbon::today()->addMonth(),
        ]);

        Suscripcion::create([
            'empresa_id' => $empresa->id, 'plan_id' => $planNegocio->id,
            'estado' => 'activa', 'monto' => $planNegocio->precio,
            'inicia_en' => Carbon::today(), 'termina_en' => Carbon::today()->addMonth(),
        ]);

        // A partir de aquí todo lo creado pertenece a esta empresa (tenant).
        Tenant::set($empresa->id);

        // ===== Usuarios =====
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@saas.test',
            'password' => 'password',
            'rol' => 'admin',
            'activo' => true,
        ]);

        User::create([
            'name' => 'Vendedor Demo',
            'email' => 'vendedor@saas.test',
            'password' => 'password',
            'rol' => 'vendedor',
            'activo' => true,
        ]);

        // ===== Categorías =====
        $categorias = collect(['Abarrotes', 'Bebidas', 'Limpieza', 'Snacks', 'Lácteos'])
            ->map(fn ($n) => Categoria::create(['nombre' => $n]));

        // ===== Marcas =====
        $marcas = collect(['Genérico', 'Kern\'s', 'Coca-Cola', 'Nestlé', 'Xelapan'])
            ->map(fn ($n) => Marca::create(['nombre' => $n]));

        // ===== Proveedores =====
        Proveedor::create(['nombre' => 'Distribuidora Central S.A.', 'ruc' => '1234567-9', 'telefono' => '2234-5678']);
        Proveedor::create(['nombre' => 'Mayorista El Sol S.A.', 'ruc' => '9876543-2', 'telefono' => '2345-6789']);

        // ===== Productos =====
        $nombres = [
            ['Arroz blanco 5lb', 18.50, 25.00, 'Abarrotes'],
            ['Aceite vegetal 1L', 12.20, 16.50, 'Abarrotes'],
            ['Azúcar Caña 1kg', 6.10, 8.50, 'Abarrotes'],
            ['Coca-Cola 500ml', 4.80, 7.00, 'Bebidas'],
            ['Agua Pura Salvavidas 600ml', 2.90, 5.00, 'Bebidas'],
            ['Jugo Kern\'s 1.5L', 8.50, 12.50, 'Bebidas'],
            ['Detergente Xedex 900g', 14.00, 18.90, 'Limpieza'],
            ['Cloro Magia Blanqueadora 1L', 5.80, 8.20, 'Limpieza'],
            ['Papel Higiénico x4', 8.20, 12.00, 'Limpieza'],
            ['Galletas Tortrix', 1.50, 2.50, 'Snacks'],
            ['Papitas Lays 145g', 6.00, 9.80, 'Snacks'],
            ['Chocolate Granada', 2.00, 3.50, 'Snacks'],
            ['Leche Parmalat 1L', 9.30, 13.90, 'Lácteos'],
            ['Yogurt Dos Pinos 1L', 12.50, 17.50, 'Lácteos'],
            ['Queso Capas 500g', 18.00, 26.00, 'Lácteos'],
        ];

        $productos = collect();
        foreach ($nombres as $i => [$nombre, $compra, $venta, $cat]) {
            $productos->push(Producto::create([
                'codigo' => 'P' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'nombre' => $nombre,
                'categoria_id' => $categorias->firstWhere('nombre', $cat)->id,
                'marca_id' => $marcas->random()->id,
                'precio_compra' => $compra,
                'precio_venta' => $venta,
                'stock' => rand(3, 80),
                'stock_minimo' => 10,
            ]));
        }

        // ===== Clientes =====
        $clientes = collect([
            ['Consumidor Final', 'CF', 'C/F'],
            ['Juan Pérez', 'DPI', '2450123450101'],
            ['María López', 'DPI', '1890543210101'],
            ['Comercial Maya S.A.', 'NIT', '1234567-9'],
            ['Carlos Ramírez', 'DPI', '1990876540101'],
            ['Abarrotería La Esperanza', 'NIT', '9876543-2'],
        ])->map(fn ($c) => Cliente::create([
            'nombre' => $c[0], 'tipo_documento' => $c[1], 'numero_documento' => $c[2],
        ]));

        // ===== Ventas demo (últimos 30 días) para poblar el dashboard =====
        $contador = 1;
        for ($d = 29; $d >= 0; $d--) {
            $fecha = Carbon::today()->subDays($d);
            $numVentas = rand(1, 6);

            for ($v = 0; $v < $numVentas; $v++) {
                $venta = Venta::create([
                    'numero' => 'V-' . str_pad($contador++, 6, '0', STR_PAD_LEFT),
                    'cliente_id' => $clientes->random()->id,
                    'user_id' => $admin->id,
                    'tipo_comprobante' => collect(['TICKET', 'BOLETA', 'FACTURA'])->random(),
                    'metodo_pago' => collect(['EFECTIVO', 'TARJETA', 'TRANSFERENCIA'])->random(),
                    'estado' => 'COMPLETADA',
                    'created_at' => $fecha->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
                    'updated_at' => $fecha,
                ]);

                $subtotal = 0;
                $items = $productos->random(rand(1, 4));
                foreach ($items as $prod) {
                    $cant = rand(1, 5);
                    $sub = $cant * $prod->precio_venta;
                    $subtotal += $sub;

                    VentaDetalle::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $prod->id,
                        'descripcion' => $prod->nombre,
                        'cantidad' => $cant,
                        'precio' => $prod->precio_venta,
                        'subtotal' => $sub,
                    ]);
                }

                $impuesto = round($subtotal * 0.12, 2);
                $venta->update([
                    'subtotal' => $subtotal,
                    'impuesto' => $impuesto,
                    'total' => $subtotal + $impuesto,
                ]);
            }
        }

        // ===================================================================
        // ===== Empresa / Tenant #2 (para demostrar el aislamiento) =========
        // ===================================================================
        $empresa2 = Empresa::create([
            'nombre' => 'Boutique Luna GT',
            'ruc' => '9876543-2',
            'direccion' => 'Calle del Arco 12, Antigua Guatemala',
            'moneda' => 'Q',
            'igv' => 12.00,
            'plan_id' => $planEmprendedor->id,
            'estado_suscripcion' => 'trial',
            'trial_termina_en' => Carbon::today()->addDays(14),
        ]);

        Suscripcion::create([
            'empresa_id' => $empresa2->id, 'plan_id' => $planEmprendedor->id,
            'estado' => 'trial', 'monto' => 0,
            'inicia_en' => Carbon::today(), 'termina_en' => Carbon::today()->addDays(14),
        ]);

        Tenant::set($empresa2->id);

        User::create([
            'name' => 'Dueña Boutique',
            'email' => 'admin@boutique.test',
            'password' => 'password',
            'rol' => 'admin',
            'activo' => true,
        ]);

        $catRopa = Categoria::create(['nombre' => 'Ropa']);
        Categoria::create(['nombre' => 'Accesorios']);

        foreach ([
            ['B0001', 'Blusa de lino', 125.00, 249.90],
            ['B0002', 'Pantalón de vestir', 140.00, 289.90],
            ['B0003', 'Cartera típica artesanal', 160.00, 329.90],
        ] as [$cod, $nom, $c, $v]) {
            Producto::create([
                'codigo' => $cod,
                'nombre' => $nom,
                'categoria_id' => $catRopa->id,
                'precio_compra' => $c,
                'precio_venta' => $v,
                'stock' => rand(5, 30),
                'stock_minimo' => 5,
            ]);
        }

        Cliente::create(['nombre' => 'Ana Torres', 'tipo_documento' => 'DPI', 'numero_documento' => '2230405010101']);

        // Limpia el tenant al finalizar el seeder.
        Tenant::clear();

        // ===== Cargar Rutas de Entrega locales =====
        $this->call(RutaSeeder::class);
    }
}
