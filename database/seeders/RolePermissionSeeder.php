<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Los permisos se asignan por código para evitar depender
         * de los IDs actuales de la base de datos.
         *
         * El seeder es idempotente:
         * puede ejecutarse nuevamente sin crear duplicados.
         */

        $permissions = Permission::query()
            ->where('status', 'activo')
            ->get()
            ->keyBy('code');

        /*
         * Super Admin
         *
         * Tiene todos los permisos disponibles del sistema.
         */
        $this->syncRolePermissions(
            'super_admin',
            $permissions->keys()->all(),
            $permissions
        );

        /*
         * Administrador
         *
         * Tiene permisos operativos completos dentro de su empresa.
         * La habilitación/deshabilitación de módulos NO se controla
         * mediante estos permisos; corresponde al nivel Super Admin.
         */
        $this->syncRolePermissions(
            'admin',
            [
                'caja.view',
                'caja.open',
                'caja.close',

                'mesas.view',
                'mesas.create',
                'mesas.update',
                'mesas.delete',

                'productos.view',
                'productos.create',
                'productos.update',
                'productos.delete',

                'ventas.view',
                'ventas.create',
                'ventas.update',
                'ventas.cancel',

                'impuestos.view',
                'impuestos.create',
                'impuestos.update',

                'descuentos.view',
                'descuentos.create',
                'descuentos.update',

                'solicitudes.view',
                'solicitudes.create',
                'solicitudes.approve',

                'eventos.view',

                'estadisticas.view',

                'inventario.view',
                'inventario.create',
                'inventario.update',

                'cocina.view',
                'cocina.update',

                'bar.view',
                'bar.update',

                'clientes.view',
                'clientes.create',
                'clientes.update',

                'compras_proveedores.view',
                'compras_proveedores.create',
                'compras_proveedores.update',

                'propinas.view',
                'propinas.create',

                'importaciones.view',
                'importaciones.create',

                'exportaciones.view',
                'exportaciones.create',

                'facturacion.view',
                'facturacion.create',
            ],
            $permissions
        );

        /*
         * Cajero
         */
        $this->syncRolePermissions(
            'cajero',
            [
                'caja.view',
                'caja.open',
                'caja.close',

                'ventas.view',
                'ventas.create',
                'ventas.update',
                'ventas.cancel',

                'clientes.view',
                'clientes.create',
                'clientes.update',

                'descuentos.view',
                'descuentos.create',
                'descuentos.update',

                'facturacion.view',
                'facturacion.create',
            ],
            $permissions
        );

        /*
         * Mesero
         */
        $this->syncRolePermissions(
            'mesero',
            [
                'mesas.view',
                'mesas.create',
                'mesas.update',

                'ventas.view',
                'ventas.create',
                'ventas.update',

                'clientes.view',
                'clientes.create',
                'clientes.update',

                'propinas.view',
                'propinas.create',
            ],
            $permissions
        );

        /*
         * Cocina
         */
        $this->syncRolePermissions(
            'cocina',
            [
                'cocina.view',
                'cocina.update',
            ],
            $permissions
        );

        /*
         * Barman
         */
        $this->syncRolePermissions(
            'barman',
            [
                'bar.view',
                'bar.update',
            ],
            $permissions
        );
    }

    /**
     * Sincroniza los permisos de un rol utilizando sus códigos.
     */
    private function syncRolePermissions(
        string $roleCode,
        array $permissionCodes,
        $permissions
    ): void {
        $role = Role::query()
            ->where('code', $roleCode)
            ->firstOrFail();

        $permissionIds = collect($permissionCodes)
            ->map(function (string $code) use ($permissions, $roleCode) {
                if (! $permissions->has($code)) {
                    throw new \RuntimeException(
                        "El permiso [{$code}] no existe o no está activo para el rol [{$roleCode}]."
                    );
                }

                return $permissions->get($code)->id;
            })
            ->values()
            ->all();

        $role->permissions()->sync($permissionIds);
    }
}