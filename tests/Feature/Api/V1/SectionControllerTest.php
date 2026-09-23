<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\Module;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SectionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(): array
    {
        $company = Company::create([
            'name' => 'Empresa Secciones Feature Test',
            'legal_name' => 'Empresa Secciones Feature Test S.A. de C.V.',
            'tax_id' => 'SECTION010101AAA',
            'email' => 'sections@test.local',
            'phone' => '7350000200',
            'status' => 'activa',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Secciones Test',
            'code' => 'SEC-001',
            'address' => 'Direccion de prueba',
            'city' => 'Cuautla',
            'state' => 'Morelos',
            'postal_code' => '62700',
            'phone' => '7350000201',
            'email' => 'sections-branch@test.local',
            'status' => 'activa',
            'is_main' => true,
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'SECTION-LICENSE-' . $company->id,
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
    ): User {
        $user = User::factory()->create([
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
            'code' => 'admin_section_test_' . $user->id,
            'name' => 'Administrador Secciones Test',
            'description' => 'Rol para pruebas de secciones.',
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

        return $user;
    }

    public function test_authenticated_user_can_list_active_sections(): void
    {
        $context = $this->createCompany();

        $user = $this->createCompanyUser(
            $context['company'],
            $context['branch']
        );

        $module = Module::create([
            'code' => 'secciones_test',
            'name' => 'Modulo Secciones Test',
            'description' => 'Modulo para pruebas de secciones.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        Section::create([
            'module_id' => $module->id,
            'code' => 'seccion_activa_test',
            'name' => 'Seccion Activa Test',
            'description' => 'Seccion activa para prueba.',
            'status' => 'activo',
            'sort_order' => 1,
            'metadata' => null,
        ]);

        Section::create([
            'module_id' => $module->id,
            'code' => 'seccion_inactiva_test',
            'name' => 'Seccion Inactiva Test',
            'description' => 'Seccion inactiva para prueba.',
            'status' => 'inactivo',
            'sort_order' => 2,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $user,
            ['api']
        );

        $response = $this->getJson(
            '/api/v1/sections?company_id=' . $context['company']->id .
                '&branch_id=' . $context['branch']->id
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ])
            ->assertJsonFragment([
                'code' => 'seccion_activa_test',
                'name' => 'Seccion Activa Test',
                'status' => 'activo',
            ])
            ->assertJsonMissing([
                'code' => 'seccion_inactiva_test',
            ]);
    }

    public function test_sections_include_their_module_data(): void
    {
        $context = $this->createCompany();

        $user = $this->createCompanyUser(
            $context['company'],
            $context['branch']
        );

        $module = Module::create([
            'code' => 'modulo_relacion_section_test',
            'name' => 'Modulo Relacion Section Test',
            'description' => 'Modulo para validar relacion.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        Section::create([
            'module_id' => $module->id,
            'code' => 'seccion_relacion_test',
            'name' => 'Seccion Relacion Test',
            'description' => 'Seccion para validar modulo relacionado.',
            'status' => 'activo',
            'sort_order' => 1,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $user,
            ['api']
        );

        $response = $this->getJson(
            '/api/v1/sections?company_id=' . $context['company']->id .
                '&branch_id=' . $context['branch']->id
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.module.id',
                $module->id
            )
            ->assertJsonPath(
                'data.0.module.code',
                'modulo_relacion_section_test'
            )
            ->assertJsonPath(
                'data.0.module.name',
                'Modulo Relacion Section Test'
            );
    }

    public function test_sections_are_ordered_by_module_and_sort_order(): void
    {
        $context = $this->createCompany();

        $user = $this->createCompanyUser(
            $context['company'],
            $context['branch']
        );

        $moduleA = Module::create([
            'code' => 'modulo_a_section_order_test',
            'name' => 'Modulo A Section Order Test',
            'description' => 'Modulo A.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 1,
            'metadata' => null,
        ]);

        $moduleB = Module::create([
            'code' => 'modulo_b_section_order_test',
            'name' => 'Modulo B Section Order Test',
            'description' => 'Modulo B.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 2,
            'metadata' => null,
        ]);

        Section::create([
            'module_id' => $moduleB->id,
            'code' => 'seccion_b_test',
            'name' => 'Seccion B Test',
            'description' => 'Seccion B.',
            'status' => 'activo',
            'sort_order' => 1,
            'metadata' => null,
        ]);

        Section::create([
            'module_id' => $moduleA->id,
            'code' => 'seccion_a_2_test',
            'name' => 'Seccion A 2 Test',
            'description' => 'Seccion A 2.',
            'status' => 'activo',
            'sort_order' => 2,
            'metadata' => null,
        ]);

        Section::create([
            'module_id' => $moduleA->id,
            'code' => 'seccion_a_1_test',
            'name' => 'Seccion A 1 Test',
            'description' => 'Seccion A 1.',
            'status' => 'activo',
            'sort_order' => 1,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $user,
            ['api']
        );

        $response = $this->getJson(
            '/api/v1/sections?company_id=' . $context['company']->id .
                '&branch_id=' . $context['branch']->id
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.code',
                'seccion_a_1_test'
            )
            ->assertJsonPath(
                'data.1.code',
                'seccion_a_2_test'
            )
            ->assertJsonPath(
                'data.2.code',
                'seccion_b_test'
            );
    }
    public function test_sections_are_catalog_sections_independent_of_company_module_activation(): void
    {
        $context = $this->createCompany();

        $user = $this->createCompanyUser(
            $context['company'],
            $context['branch']
        );

        $module = Module::create([
            'code' => 'modulo_no_habilitado_test',
            'name' => 'Modulo No Habilitado Test',
            'description' => 'Modulo global activo pero no habilitado para la empresa.',
            'status' => 'activo',
            'is_core' => false,
            'sort_order' => 999,
            'metadata' => null,
        ]);

        Section::create([
            'module_id' => $module->id,
            'code' => 'seccion_catalogo_test',
            'name' => 'Seccion Catalogo Test',
            'description' => 'Seccion del catalogo global.',
            'status' => 'activo',
            'sort_order' => 1,
            'metadata' => null,
        ]);

        Sanctum::actingAs(
            $user,
            ['api']
        );

        $response = $this->getJson(
            '/api/v1/sections?company_id=' . $context['company']->id .
                '&branch_id=' . $context['branch']->id
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'code' => 'seccion_catalogo_test',
                'name' => 'Seccion Catalogo Test',
                'status' => 'activo',
            ]);
    }
    public function test_unauthenticated_user_cannot_access_sections(): void
    {
        $response = $this->getJson('/api/v1/sections');

        $response->assertUnauthorized();
    }
}
