<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'code' => 'caja',
                'name' => 'Caja',
                'description' => 'Gestión de cajas, aperturas, movimientos y cierres.',
                'status' => 'activo',
                'is_core' => true,
                'sort_order' => 10,
                'metadata' => ['group' => 'core'],
            ],
            [
                'code' => 'mesas',
                'name' => 'Mesas',
                'description' => 'Gestión de mesas y operación de atención en restaurante.',
                'status' => 'activo',
                'is_core' => true,
                'sort_order' => 20,
                'metadata' => ['group' => 'core'],
            ],
            [
                'code' => 'productos',
                'name' => 'Productos',
                'description' => 'Catálogo y administración de productos.',
                'status' => 'activo',
                'is_core' => true,
                'sort_order' => 30,
                'metadata' => ['group' => 'core'],
            ],
            [
                'code' => 'ventas',
                'name' => 'Ventas',
                'description' => 'Registro y gestión de ventas.',
                'status' => 'activo',
                'is_core' => true,
                'sort_order' => 40,
                'metadata' => ['group' => 'core'],
            ],
            [
                'code' => 'impuestos',
                'name' => 'Impuestos',
                'description' => 'Configuración y aplicación de impuestos.',
                'status' => 'activo',
                'is_core' => true,
                'sort_order' => 50,
                'metadata' => ['group' => 'core'],
            ],
            [
                'code' => 'descuentos',
                'name' => 'Descuentos',
                'description' => 'Configuración y aplicación de descuentos.',
                'status' => 'activo',
                'is_core' => true,
                'sort_order' => 60,
                'metadata' => ['group' => 'core'],
            ],
            [
                'code' => 'solicitudes',
                'name' => 'Solicitudes',
                'description' => 'Gestión de solicitudes y autorizaciones operativas.',
                'status' => 'activo',
                'is_core' => true,
                'sort_order' => 70,
                'metadata' => ['group' => 'core'],
            ],
            [
                'code' => 'eventos',
                'name' => 'Eventos',
                'description' => 'Gestión de eventos internos y comunicación en tiempo real.',
                'status' => 'activo',
                'is_core' => true,
                'sort_order' => 80,
                'metadata' => ['group' => 'core'],
            ],
            [
                'code' => 'estadisticas',
                'name' => 'Estadísticas',
                'description' => 'Indicadores, métricas y estadísticas del sistema.',
                'status' => 'activo',
                'is_core' => true,
                'sort_order' => 90,
                'metadata' => ['group' => 'core'],
            ],
            [
                'code' => 'inventario',
                'name' => 'Inventario',
                'description' => 'Control de existencias y movimientos de inventario.',
                'status' => 'activo',
                'is_core' => false,
                'sort_order' => 100,
                'metadata' => ['group' => 'optional'],
            ],
            [
                'code' => 'cocina',
                'name' => 'Cocina',
                'description' => 'Gestión de pedidos y operación de cocina.',
                'status' => 'activo',
                'is_core' => false,
                'sort_order' => 110,
                'metadata' => ['group' => 'optional'],
            ],
            [
                'code' => 'bar',
                'name' => 'Bar',
                'description' => 'Gestión de pedidos y operación de barra.',
                'status' => 'activo',
                'is_core' => false,
                'sort_order' => 120,
                'metadata' => ['group' => 'optional'],
            ],
            [
                'code' => 'clientes',
                'name' => 'Clientes',
                'description' => 'Gestión de clientes y sus datos comerciales.',
                'status' => 'activo',
                'is_core' => false,
                'sort_order' => 130,
                'metadata' => ['group' => 'optional'],
            ],
            [
                'code' => 'compras_proveedores',
                'name' => 'Compras y Proveedores',
                'description' => 'Gestión conjunta de compras y proveedores.',
                'status' => 'activo',
                'is_core' => false,
                'sort_order' => 140,
                'metadata' => ['group' => 'optional'],
            ],
            [
                'code' => 'propinas',
                'name' => 'Propinas',
                'description' => 'Gestión y aplicación de propinas.',
                'status' => 'activo',
                'is_core' => false,
                'sort_order' => 150,
                'metadata' => ['group' => 'optional'],
            ],
            [
                'code' => 'importaciones',
                'name' => 'Importaciones',
                'description' => 'Importación masiva de información mediante archivos.',
                'status' => 'activo',
                'is_core' => false,
                'sort_order' => 160,
                'metadata' => ['group' => 'optional'],
            ],
            [
                'code' => 'exportaciones',
                'name' => 'Exportaciones',
                'description' => 'Exportación de información y reportes.',
                'status' => 'activo',
                'is_core' => false,
                'sort_order' => 170,
                'metadata' => ['group' => 'optional'],
            ],
            [
                'code' => 'facturacion',
                'name' => 'Facturación',
                'description' => 'Gestión de facturación y procesos fiscales.',
                'status' => 'activo',
                'is_core' => false,
                'sort_order' => 180,
                'metadata' => ['group' => 'optional'],
            ],
        ];

        foreach ($modules as $module) {
            Module::query()->updateOrCreate(
                ['code' => $module['code']],
                $module
            );
        }
    }
}
