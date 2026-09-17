<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ruta;
use App\Support\Tenant;
use Illuminate\Database\Seeder;

class RutaSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = Empresa::all();

        foreach ($empresas as $empresa) {
            Tenant::set($empresa->id);

            $rutasData = [
                [
                    'nombre' => 'Ruta 1 - Carretera a El Salvador & Pinula',
                    'codigo' => 'RUT-GT-01',
                    'dias_entrega_estimados' => 1,
                    'descripcion' => 'Cobertura en Zona 10, Santa Catarina Pinula, Fraijanes, Carretera a El Salvador y San José Pinula.',
                    'activo' => true,
                ],
                [
                    'nombre' => 'Ruta 2 - Mixco, Roosevelt & San Cristóbal',
                    'codigo' => 'RUT-GT-02',
                    'dias_entrega_estimados' => 1,
                    'descripcion' => 'Cobertura en Zonas 7 y 11, Calzada Roosevelt, Ciudad San Cristóbal, San Lucas y Antigua Guatemala.',
                    'activo' => true,
                ],
                [
                    'nombre' => 'Ruta 3 - Norte (Zona 18, Palencia & El Progreso)',
                    'codigo' => 'RUT-GT-03',
                    'dias_entrega_estimados' => 2,
                    'descripcion' => 'Cobertura en Zonas 17 y 18, Palencia, Sanarate, Guastatoya y El Progreso.',
                    'activo' => true,
                ],
                [
                    'nombre' => 'Ruta 4 - Sur (Villa Nueva, Amatitlán & Escuintla)',
                    'codigo' => 'RUT-GT-04',
                    'dias_entrega_estimados' => 2,
                    'descripcion' => 'Cobertura en Calzada Aguilar Batres, Villa Nueva, Amatitlán, Palín, Escuintla y Sta. Lucía Cotzumalguapa.',
                    'activo' => true,
                ],
                [
                    'nombre' => 'Ruta 5 - Occidente (Chimaltenango & Xela Centro)',
                    'codigo' => 'RUT-GT-05',
                    'dias_entrega_estimados' => 3,
                    'descripcion' => 'Cobertura departamental en Chimaltenango, Tecpán, Sololá, Totonicapán y Quetzaltenango (Xela).',
                    'activo' => true,
                ],
            ];

            $rutasCreadas = [];
            foreach ($rutasData as $data) {
                $rutasCreadas[] = Ruta::create($data);
            }

            // Asignar rutas predeterminadas a los clientes existentes de la empresa
            $clientes = Cliente::all();
            foreach ($clientes as $index => $cliente) {
                if ($cliente->nombre !== 'Consumidor Final') {
                    $rutaAsignada = $rutasCreadas[$index % count($rutasCreadas)];
                    $cliente->update(['ruta_id' => $rutaAsignada->id]);
                }
            }

            Tenant::clear();
        }
    }
}
