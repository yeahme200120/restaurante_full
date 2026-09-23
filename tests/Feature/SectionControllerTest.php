<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\Module;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_active_sections(): void
    {
        $company = Company::create([
            'name' => 'Empresa de Prueba',
            'legal_name' => 'Empresa de Prueba S.A. de C.V.',
            'tax_id' => 'TAX-TEST-001',
            'email' => 'empresa@test.local',
            'status' => 'activa',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'code' => 'SUC-001',
            'name' => 'Sucursal Principal',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-LICENSE-001',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $user = User::create([
            'name' => 'Usuario de Prueba',
            'email' => 'sections@test.local',
            'password' => Hash::make('password'),
            'status' => 'activo',
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $user->branches()->attach($branch->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $role = Role::create([
            'code' => 'admin',
            'name' => 'Administrador',
            'status' => 'activo',
            'is_system' => false,
            'scope' => 'company',
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'company_id' => $company->id,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        $module = Module::create([
            'code' => 'ventas',
            'name' => 'Ventas',
            'description' => 'Módulo de ventas',
            'status' => 'activo',
            'is_core' => true,
            'sort_order' => 1,
            'metadata' => null,
        ]);

        $activeSection = Section::create([
            'module_id' => $module->id,
            'code' => 'ventas_principal',
            'name' => 'Ventas Principal',
            'description' => 'Sección activa de ventas',
            'status' => 'activo',
            'sort_order' => 1,
            'metadata' => null,
        ]);

        Section::create([
            'module_id' => $module->id,
            'code' => 'ventas_inactiva',
            'name' => 'Ventas Inactiva',
            'description' => 'Sección inactiva de ventas',
            'status' => 'inactivo',
            'sort_order' => 2,
            'metadata' => null,
        ]);

        Sanctum::actingAs($user, ['api']);

        $response = $this->getJson('/api/v1/sections');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'module_id',
                        'code',
                        'name',
                        'description',
                        'status',
                        'sort_order',
                        'metadata',
                        'module',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'id' => $activeSection->id,
                'code' => 'ventas_principal',
                'status' => 'activo',
            ])
            ->assertJsonMissing([
                'code' => 'ventas_inactiva',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_sections(): void
    {
        $response = $this->getJson('/api/v1/sections');

        $response->assertUnauthorized();
    }
}