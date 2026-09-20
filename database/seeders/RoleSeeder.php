<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'code' => 'super_admin',
                'name' => 'Super Admin',
                'description' => 'Administrador global del sistema.',
                'status' => 'activo',
                'is_system' => true,
                'scope' => 'global',
            ],
            [
                'code' => 'admin',
                'name' => 'Administrador',
                'description' => 'Administrador operativo de una empresa.',
                'status' => 'activo',
                'is_system' => true,
                'scope' => 'company',
            ],
            [
                'code' => 'cajero',
                'name' => 'Cajero',
                'description' => 'Usuario encargado de operaciones de caja y ventas autorizadas.',
                'status' => 'activo',
                'is_system' => true,
                'scope' => 'company',
            ],
            [
                'code' => 'mesero',
                'name' => 'Mesero',
                'description' => 'Usuario encargado de mesas y operaciones de venta correspondientes.',
                'status' => 'activo',
                'is_system' => true,
                'scope' => 'company',
            ],
            [
                'code' => 'cocina',
                'name' => 'Cocina',
                'description' => 'Usuario encargado de operaciones del módulo de cocina.',
                'status' => 'activo',
                'is_system' => true,
                'scope' => 'company',
            ],
            [
                'code' => 'barman',
                'name' => 'Barman',
                'description' => 'Usuario encargado de operaciones del módulo de bar.',
                'status' => 'activo',
                'is_system' => true,
                'scope' => 'company',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['code' => $role['code']],
                $role
            );
        }
    }
}