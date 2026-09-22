<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthorizationTestSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Empresa de prueba
         */
        $company = Company::updateOrCreate(
            [
                'tax_id' => 'TEST-AUTH-001',
            ],
            [
                'name' => 'Restaurante de Prueba',
                'legal_name' => 'Restaurante de Prueba S.A. de C.V.',
                'tax_id' => 'TEST-AUTH-001',
                'email' => 'test@restaurante.local',
                'phone' => '5555555555',
                'status' => 'activa',
                'timezone' => 'America/Mexico_City',
                'locale' => 'es',
                'notes' => 'Datos utilizados para pruebas de autorización.',
            ]
        );

        /*
         * Licencia activa y vigente
         */
        CompanyLicense::updateOrCreate(
            [
                'license_key' => 'TEST-AUTH-LICENSE-001',
            ],
            [
                'company_id' => $company->id,
                'plan' => 'standard',
                'status' => 'activa',
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addYear(),
                'revoked_at' => null,
                'notes' => 'Licencia de prueba para autorización.',
            ]
        );

        /*
         * Sucursal principal
         */
        $branch = Branch::updateOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'TEST-001',
            ],
            [
                'name' => 'Sucursal Principal',
                'code' => 'TEST-001',
                'address' => 'Dirección de prueba',
                'city' => 'Ciudad de México',
                'state' => 'Ciudad de México',
                'postal_code' => '00000',
                'phone' => '5555555555',
                'email' => 'sucursal@restaurante.local',
                'status' => 'activa',
                'is_main' => true,
            ]
        );

        /*
         * Usuario administrador de prueba
         */
        $user = User::updateOrCreate(
            [
                'email' => 'admin.test@restaurante.local',
            ],
            [
                'name' => 'Administrador de Prueba',
                'phone' => '5555555555',
                'password' => Hash::make('Password123!'),
                'status' => 'activo',
            ]
        );

        /*
         * Asignación usuario → empresa
         */
        $user->companies()->syncWithoutDetaching([
            $company->id => [
                'is_default' => true,
                'status' => 'activa',
            ],
        ]);

        /*
         * Asignación usuario → sucursal
         */
        $user->branches()->syncWithoutDetaching([
            $branch->id => [
                'is_default' => true,
                'status' => 'activa',
            ],
        ]);

        /*
         * Rol Administrador
         */
        $role = Role::query()
            ->where('code', 'admin')
            ->where('status', 'activo')
            ->firstOrFail();

        UserRole::updateOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'company_id' => $company->id,
            ],
            [
                'status' => 'activo',
                'assigned_at' => now(),
            ]
        );

        $this->command?->info(
            'Datos de prueba de autorización creados/actualizados correctamente.'
        );

        $this->command?->info(
            "Empresa ID: {$company->id}"
        );

        $this->command?->info(
            "Sucursal ID: {$branch->id}"
        );

        $this->command?->info(
            "Usuario ID: {$user->id}"
        );

        $this->command?->info(
            'Usuario: admin.test@restaurante.local'
        );

        $this->command?->info(
            'Contraseña: Password123!'
        );

        $this->command?->info(
            'Rol: admin'
        );
    }
}
