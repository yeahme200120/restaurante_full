<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'code' => 'caja.view',
                'name' => 'Consultar caja',
                'description' => 'Permite consultar información de caja.',
                'module' => 'caja',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'caja.open',
                'name' => 'Abrir caja',
                'description' => 'Permite abrir una caja.',
                'module' => 'caja',
                'section' => null,
                'action' => 'open',
            ],
            [
                'code' => 'caja.close',
                'name' => 'Cerrar caja',
                'description' => 'Permite cerrar una caja.',
                'module' => 'caja',
                'section' => null,
                'action' => 'close',
            ],

            [
                'code' => 'mesas.view',
                'name' => 'Consultar mesas',
                'description' => 'Permite consultar las mesas.',
                'module' => 'mesas',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'mesas.create',
                'name' => 'Crear mesa',
                'description' => 'Permite crear mesas.',
                'module' => 'mesas',
                'section' => null,
                'action' => 'create',
            ],
            [
                'code' => 'mesas.update',
                'name' => 'Actualizar mesa',
                'description' => 'Permite actualizar mesas.',
                'module' => 'mesas',
                'section' => null,
                'action' => 'update',
            ],
            [
                'code' => 'mesas.delete',
                'name' => 'Eliminar mesa',
                'description' => 'Permite eliminar mesas.',
                'module' => 'mesas',
                'section' => null,
                'action' => 'delete',
            ],

            [
                'code' => 'productos.view',
                'name' => 'Consultar productos',
                'description' => 'Permite consultar productos.',
                'module' => 'productos',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'productos.create',
                'name' => 'Crear producto',
                'description' => 'Permite crear productos.',
                'module' => 'productos',
                'section' => null,
                'action' => 'create',
            ],
            [
                'code' => 'productos.update',
                'name' => 'Actualizar producto',
                'description' => 'Permite actualizar productos.',
                'module' => 'productos',
                'section' => null,
                'action' => 'update',
            ],
            [
                'code' => 'productos.delete',
                'name' => 'Eliminar producto',
                'description' => 'Permite eliminar productos.',
                'module' => 'productos',
                'section' => null,
                'action' => 'delete',
            ],

            [
                'code' => 'ventas.view',
                'name' => 'Consultar ventas',
                'description' => 'Permite consultar ventas.',
                'module' => 'ventas',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'ventas.create',
                'name' => 'Crear venta',
                'description' => 'Permite crear ventas.',
                'module' => 'ventas',
                'section' => null,
                'action' => 'create',
            ],
            [
                'code' => 'ventas.update',
                'name' => 'Actualizar venta',
                'description' => 'Permite actualizar ventas.',
                'module' => 'ventas',
                'section' => null,
                'action' => 'update',
            ],
            [
                'code' => 'ventas.cancel',
                'name' => 'Cancelar venta',
                'description' => 'Permite cancelar ventas.',
                'module' => 'ventas',
                'section' => null,
                'action' => 'cancel',
            ],

            [
                'code' => 'impuestos.view',
                'name' => 'Consultar impuestos',
                'description' => 'Permite consultar impuestos.',
                'module' => 'impuestos',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'impuestos.create',
                'name' => 'Crear impuesto',
                'description' => 'Permite crear impuestos.',
                'module' => 'impuestos',
                'section' => null,
                'action' => 'create',
            ],
            [
                'code' => 'impuestos.update',
                'name' => 'Actualizar impuesto',
                'description' => 'Permite actualizar impuestos.',
                'module' => 'impuestos',
                'section' => null,
                'action' => 'update',
            ],

            [
                'code' => 'descuentos.view',
                'name' => 'Consultar descuentos',
                'description' => 'Permite consultar descuentos.',
                'module' => 'descuentos',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'descuentos.create',
                'name' => 'Crear descuento',
                'description' => 'Permite crear descuentos.',
                'module' => 'descuentos',
                'section' => null,
                'action' => 'create',
            ],
            [
                'code' => 'descuentos.update',
                'name' => 'Actualizar descuento',
                'description' => 'Permite actualizar descuentos.',
                'module' => 'descuentos',
                'section' => null,
                'action' => 'update',
            ],

            [
                'code' => 'solicitudes.view',
                'name' => 'Consultar solicitudes',
                'description' => 'Permite consultar solicitudes.',
                'module' => 'solicitudes',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'solicitudes.create',
                'name' => 'Crear solicitud',
                'description' => 'Permite crear solicitudes.',
                'module' => 'solicitudes',
                'section' => null,
                'action' => 'create',
            ],
            [
                'code' => 'solicitudes.approve',
                'name' => 'Aprobar solicitudes',
                'description' => 'Permite aprobar solicitudes.',
                'module' => 'solicitudes',
                'section' => null,
                'action' => 'approve',
            ],

            [
                'code' => 'eventos.view',
                'name' => 'Consultar eventos',
                'description' => 'Permite consultar eventos.',
                'module' => 'eventos',
                'section' => null,
                'action' => 'view',
            ],

            [
                'code' => 'estadisticas.view',
                'name' => 'Consultar estadísticas',
                'description' => 'Permite consultar estadísticas.',
                'module' => 'estadisticas',
                'section' => null,
                'action' => 'view',
            ],

            [
                'code' => 'inventario.view',
                'name' => 'Consultar inventario',
                'description' => 'Permite consultar inventario cuando el módulo está habilitado.',
                'module' => 'inventario',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'inventario.create',
                'name' => 'Crear movimiento de inventario',
                'description' => 'Permite crear movimientos de inventario.',
                'module' => 'inventario',
                'section' => null,
                'action' => 'create',
            ],
            [
                'code' => 'inventario.update',
                'name' => 'Actualizar inventario',
                'description' => 'Permite actualizar inventario.',
                'module' => 'inventario',
                'section' => null,
                'action' => 'update',
            ],

            [
                'code' => 'cocina.view',
                'name' => 'Consultar cocina',
                'description' => 'Permite consultar operaciones de cocina.',
                'module' => 'cocina',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'cocina.update',
                'name' => 'Actualizar cocina',
                'description' => 'Permite actualizar operaciones de cocina.',
                'module' => 'cocina',
                'section' => null,
                'action' => 'update',
            ],

            [
                'code' => 'bar.view',
                'name' => 'Consultar bar',
                'description' => 'Permite consultar operaciones del bar.',
                'module' => 'bar',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'bar.update',
                'name' => 'Actualizar bar',
                'description' => 'Permite actualizar operaciones del bar.',
                'module' => 'bar',
                'section' => null,
                'action' => 'update',
            ],

            [
                'code' => 'clientes.view',
                'name' => 'Consultar clientes',
                'description' => 'Permite consultar clientes.',
                'module' => 'clientes',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'clientes.create',
                'name' => 'Crear cliente',
                'description' => 'Permite crear clientes.',
                'module' => 'clientes',
                'section' => null,
                'action' => 'create',
            ],
            [
                'code' => 'clientes.update',
                'name' => 'Actualizar cliente',
                'description' => 'Permite actualizar clientes.',
                'module' => 'clientes',
                'section' => null,
                'action' => 'update',
            ],

            [
                'code' => 'compras_proveedores.view',
                'name' => 'Consultar compras y proveedores',
                'description' => 'Permite consultar compras y proveedores.',
                'module' => 'compras_proveedores',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'compras_proveedores.create',
                'name' => 'Crear compra o proveedor',
                'description' => 'Permite crear compras o proveedores.',
                'module' => 'compras_proveedores',
                'section' => null,
                'action' => 'create',
            ],
            [
                'code' => 'compras_proveedores.update',
                'name' => 'Actualizar compras y proveedores',
                'description' => 'Permite actualizar compras y proveedores.',
                'module' => 'compras_proveedores',
                'section' => null,
                'action' => 'update',
            ],

            [
                'code' => 'propinas.view',
                'name' => 'Consultar propinas',
                'description' => 'Permite consultar propinas.',
                'module' => 'propinas',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'propinas.create',
                'name' => 'Registrar propina',
                'description' => 'Permite registrar propinas.',
                'module' => 'propinas',
                'section' => null,
                'action' => 'create',
            ],

            [
                'code' => 'importaciones.view',
                'name' => 'Consultar importaciones',
                'description' => 'Permite consultar importaciones.',
                'module' => 'importaciones',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'importaciones.create',
                'name' => 'Ejecutar importación',
                'description' => 'Permite ejecutar importaciones.',
                'module' => 'importaciones',
                'section' => null,
                'action' => 'create',
            ],

            [
                'code' => 'exportaciones.view',
                'name' => 'Consultar exportaciones',
                'description' => 'Permite consultar exportaciones.',
                'module' => 'exportaciones',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'exportaciones.create',
                'name' => 'Ejecutar exportación',
                'description' => 'Permite ejecutar exportaciones.',
                'module' => 'exportaciones',
                'section' => null,
                'action' => 'create',
            ],

            [
                'code' => 'facturacion.view',
                'name' => 'Consultar facturación',
                'description' => 'Permite consultar información de facturación.',
                'module' => 'facturacion',
                'section' => null,
                'action' => 'view',
            ],
            [
                'code' => 'facturacion.create',
                'name' => 'Generar factura',
                'description' => 'Permite generar facturas.',
                'module' => 'facturacion',
                'section' => null,
                'action' => 'create',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['code' => $permission['code']],
                [
                    ...$permission,
                    'status' => 'activo',
                ]
            );
        }
    }
}