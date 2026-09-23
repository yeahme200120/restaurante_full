<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModuleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(): array
    {
        $company = Company::create([
            'name' => 'Empresa Modulos Feature Test',
            'legal_name' => 'Empresa Modulos Feature Test S.A. de C.V.',
            'tax_id' => 'MODULE010101AAA',
            'email' => 'modules@test.local',
            'phone' => '7350000100',
            'status' => 'activa',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Modulos Test',
            'code' => 'MOD-001',
            'address' => 'Direccion de prueba',
            'city' => 'Cuautla',
            'state' => 'Morelos',
            'postal_code' => '62700',
            'phone' => '7350000101',
            'email' => 'modules-branch@test.local',
            'status' => 'activa',
            'is_main' => true,
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'MODULE-LICENSE-' . $company->id,
            'plan' => 'pro',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'revoked_at' => null,
        ]);

        return [
            'company' => $company,
            'branch' => $branch,
        ];
    }

    private function createCompanyUser(
        Company $company,
        Branch $branch
    ): array {
        $user = User::factory()->create([
            'status' => 'activo',
            'password' => Hash::make('password123'),
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
            'code' => 'admin_module_test_' . $user->id,
            'name' => 'Administrador Modulos Test',
            'description' => 'Rol para pruebas de modulos.',
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

        return [
            'user' => $user,
            'role' => $role,
        ];
    }

    private function createSuperAdmin(): array
    {
        $user = User::factory()->create([
            'status' => 'activo',
            'password' => Hash::make('password123'),
        ]);

        $role = Role::create([
            'code' => 'super_admin',
            'name' => 'Super Admin',
            'description' => 'Rol global para pruebas de modulos.',
            'status' => 'activo',
            'is_system' => true,
            'scope' => 'global',
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'company_id' => null,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        return [
            'user' => $user,
            'role' => $role,
        ];
    }

    public function test_authenticated_user_can_list_active_modules(): void
    {
        $context = $this->createCompany();
        $userContext = $this->createCompanyUser(
            $context['company'],
            $context['branch']
        );

        Sanctum::actingAs(
            $userContext['user'],
            ['api']
        );

        Module::create([
            'code' => 'modulo_test',
            'name' => 'Modulo Test',
            'description' => 'Modulo para prueba.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        $response = $this->getJson(
            '/api/v1/modules?company_id=' . $context['company']->id
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ])
            ->assertJsonFragment([
                'code' => 'modulo_test',
                'name' => 'Modulo Test',
                'status' => 'activo',
            ]);
    }

    public function test_authenticated_user_can_list_enabled_company_modules(): void
    {
        $context = $this->createCompany();
        $userContext = $this->createCompanyUser(
            $context['company'],
            $context['branch']
        );

        $module = Module::create([
            'code' => 'modulo_habilitado_test',
            'name' => 'Modulo Habilitado Test',
            'description' => 'Modulo habilitado para prueba.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        CompanyModule::create([
            'company_id' => $context['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
            'enabled_at' => now(),
            'disabled_at' => null,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $userContext['user'],
            ['api']
        );

        $response = $this->getJson(
            '/api/v1/company/modules?company_id=' . $context['company']->id
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $module->id,
                'code' => 'modulo_habilitado_test',
                'name' => 'Modulo Habilitado Test',
            ]);
    }

    public function test_super_admin_can_enable_module(): void
    {
        $context = $this->createCompany();
        $superAdmin = $this->createSuperAdmin();

        $module = Module::create([
            'code' => 'modulo_enable_test',
            'name' => 'Modulo Enable Test',
            'description' => 'Modulo para habilitar.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $superAdmin['user'],
            ['api']
        );

        $response = $this->postJson(
            '/api/v1/company/modules/' . $module->id . '/enable',
            [
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Módulo habilitado correctamente.',
            ]);

        $this->assertDatabaseHas('company_modules', [
            'company_id' => $context['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'module.enabled',
            'module' => 'modulo_enable_test',
            'result' => 'success',
            'user_id' => $superAdmin['user']->id,
            'company_id' => $context['company']->id,
        ]);
    }

    public function test_non_super_admin_cannot_enable_module(): void
    {
        $context = $this->createCompany();
        $userContext = $this->createCompanyUser(
            $context['company'],
            $context['branch']
        );

        $module = Module::create([
            'code' => 'modulo_enable_denied_test',
            'name' => 'Modulo Enable Denied Test',
            'description' => 'Modulo para negar habilitacion.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $userContext['user'],
            ['api']
        );

        $response = $this->postJson(
            '/api/v1/company/modules/' . $module->id . '/enable',
            [
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
            ]
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Solo el Super Admin puede habilitar módulos.',
            ]);

        $this->assertDatabaseMissing('company_modules', [
            'company_id' => $context['company']->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_super_admin_can_disable_module(): void
    {
        $context = $this->createCompany();
        $superAdmin = $this->createSuperAdmin();

        $module = Module::create([
            'code' => 'modulo_disable_test',
            'name' => 'Modulo Disable Test',
            'description' => 'Modulo para deshabilitar.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        CompanyModule::create([
            'company_id' => $context['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
            'enabled_at' => now(),
            'disabled_at' => null,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $superAdmin['user'],
            ['api']
        );

        $response = $this->postJson(
            '/api/v1/company/modules/' . $module->id . '/disable',
            [
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Módulo deshabilitado correctamente.',
            ]);

        $this->assertDatabaseHas('company_modules', [
            'company_id' => $context['company']->id,
            'module_id' => $module->id,
            'status' => 'inactivo',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'module.disabled',
            'module' => 'modulo_disable_test',
            'result' => 'success',
            'user_id' => $superAdmin['user']->id,
            'company_id' => $context['company']->id,
        ]);
    }

    public function test_non_super_admin_cannot_disable_module(): void
    {
        $context = $this->createCompany();
        $userContext = $this->createCompanyUser(
            $context['company'],
            $context['branch']
        );

        $module = Module::create([
            'code' => 'modulo_disable_denied_test',
            'name' => 'Modulo Disable Denied Test',
            'description' => 'Modulo para negar deshabilitacion.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        CompanyModule::create([
            'company_id' => $context['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
            'enabled_at' => now(),
            'disabled_at' => null,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $userContext['user'],
            ['api']
        );

        $response = $this->postJson(
            '/api/v1/company/modules/' . $module->id . '/disable',
            [
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
            ]
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Solo el Super Admin puede deshabilitar módulos.',
            ]);

        $this->assertDatabaseHas('company_modules', [
            'company_id' => $context['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
        ]);
    }
    public function test_user_cannot_access_enabled_modules_from_another_company(): void
    {
        $companyA = $this->createCompany();
        $companyB = $this->createCompany();

        $userContext = $this->createCompanyUser(
            $companyA['company'],
            $companyA['branch']
        );

        $module = Module::create([
            'code' => 'modulo_company_b_test',
            'name' => 'Modulo Empresa B Test',
            'description' => 'Modulo habilitado solamente en empresa B.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        CompanyModule::create([
            'company_id' => $companyB['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
            'enabled_at' => now(),
            'disabled_at' => null,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $userContext['user'],
            ['api']
        );

        $response = $this->getJson(
            '/api/v1/company/modules?company_id=' . $companyB['company']->id
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('company_modules', [
            'company_id' => $companyB['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
        ]);
    }

    public function test_super_admin_can_access_enabled_modules_from_multiple_companies(): void
    {
        $companyA = $this->createCompany();
        $companyB = $this->createCompany();

        $superAdmin = $this->createSuperAdmin();

        $moduleA = Module::create([
            'code' => 'modulo_company_a_test',
            'name' => 'Modulo Empresa A Test',
            'description' => 'Modulo habilitado en empresa A.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        $moduleB = Module::create([
            'code' => 'modulo_company_b_test',
            'name' => 'Modulo Empresa B Test',
            'description' => 'Modulo habilitado en empresa B.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 1000,
            'metadata' => null,
        ]);

        CompanyModule::create([
            'company_id' => $companyA['company']->id,
            'module_id' => $moduleA->id,
            'status' => 'activo',
            'enabled_at' => now(),
            'disabled_at' => null,
            'metadata' => null,
        ]);

        CompanyModule::create([
            'company_id' => $companyB['company']->id,
            'module_id' => $moduleB->id,
            'status' => 'activo',
            'enabled_at' => now(),
            'disabled_at' => null,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $superAdmin['user'],
            ['api']
        );

        $responseA = $this->getJson(
            '/api/v1/company/modules?company_id=' . $companyA['company']->id .
                '&branch_id=' . $companyA['branch']->id
        );

        $responseA
            ->assertOk()
            ->assertJsonFragment([
                'code' => 'modulo_company_a_test',
            ])
            ->assertJsonMissing([
                'code' => 'modulo_company_b_test',
            ]);

        $responseB = $this->getJson(
            '/api/v1/company/modules?company_id=' . $companyB['company']->id .
                '&branch_id=' . $companyB['branch']->id
        );

        $responseB
            ->assertOk()
            ->assertJsonFragment([
                'code' => 'modulo_company_b_test',
            ])
            ->assertJsonMissing([
                'code' => 'modulo_company_a_test',
            ]);
    }

    public function test_super_admin_enable_module_affects_only_selected_company(): void
    {
        $companyA = $this->createCompany();
        $companyB = $this->createCompany();

        $superAdmin = $this->createSuperAdmin();

        $module = Module::create([
            'code' => 'modulo_isolation_enable_test',
            'name' => 'Modulo Isolation Enable Test',
            'description' => 'Modulo para validar aislamiento al habilitar.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $superAdmin['user'],
            ['api']
        );

        $response = $this->postJson(
            '/api/v1/company/modules/' . $module->id . '/enable',
            [
                'company_id' => $companyB['company']->id,
                'branch_id' => $companyB['branch']->id,
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('company_modules', [
            'company_id' => $companyB['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
        ]);

        $this->assertDatabaseMissing('company_modules', [
            'company_id' => $companyA['company']->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_super_admin_disable_module_affects_only_selected_company(): void
    {
        $companyA = $this->createCompany();
        $companyB = $this->createCompany();

        $superAdmin = $this->createSuperAdmin();

        $module = Module::create([
            'code' => 'modulo_isolation_disable_test',
            'name' => 'Modulo Isolation Disable Test',
            'description' => 'Modulo para validar aislamiento al deshabilitar.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        CompanyModule::create([
            'company_id' => $companyA['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
            'enabled_at' => now(),
            'disabled_at' => null,
            'metadata' => null,
        ]);

        CompanyModule::create([
            'company_id' => $companyB['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
            'enabled_at' => now(),
            'disabled_at' => null,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $superAdmin['user'],
            ['api']
        );

        $response = $this->postJson(
            '/api/v1/company/modules/' . $module->id . '/disable',
            [
                'company_id' => $companyB['company']->id,
                'branch_id' => $companyB['branch']->id,
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('company_modules', [
            'company_id' => $companyB['company']->id,
            'module_id' => $module->id,
            'status' => 'inactivo',
        ]);

        $this->assertDatabaseHas('company_modules', [
            'company_id' => $companyA['company']->id,
            'module_id' => $module->id,
            'status' => 'activo',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_modules(): void
    {
        $response = $this->getJson('/api/v1/modules');

        $response->assertUnauthorized();
    }
}
