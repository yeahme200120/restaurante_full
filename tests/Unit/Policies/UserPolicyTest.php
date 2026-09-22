<?php

namespace Tests\Unit\Policies;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(
        string $suffix
    ): array {
        $company = Company::create([
            'name' => 'Empresa Policy ' . $suffix,
            'legal_name' => 'Empresa Policy ' . $suffix . ' S.A. de C.V.',
            'tax_id' => 'POLICY' . $suffix . 'AAA',
            'email' => 'policy-' . strtolower($suffix) . '@test.local',
            'phone' => '7350001000',
            'status' => 'activa',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Policy ' . $suffix,
            'code' => 'POLICY-' . $suffix,
            'address' => 'Direccion de prueba',
            'city' => 'Cuautla',
            'state' => 'Morelos',
            'postal_code' => '62700',
            'phone' => '7350001001',
            'email' => 'branch-' . strtolower($suffix) . '@test.local',
            'status' => 'activa',
            'is_main' => true,
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'POLICY-LICENSE-' . $company->id,
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

    private function createPermission(
        string $code
    ): Permission {
        return Permission::firstOrCreate(
            ['code' => $code],
            [
                'name' => $code,
                'description' => 'Permiso ' . $code,
                'module' => 'usuarios',
                'section' => 'usuarios',
                'action' => str_replace('usuarios.', '', $code),
                'status' => 'activo',
            ]
        );
    }

    private function createCompanyRole(
        string $suffix,
        array $permissionCodes
    ): Role {
        $role = Role::create([
            'code' => 'admin_policy_' . strtolower($suffix),
            'name' => 'Administrador Policy ' . $suffix,
            'description' => 'Rol para pruebas de UserPolicy.',
            'status' => 'activo',
            'is_system' => false,
            'scope' => 'company',
        ]);

        $permissions = collect();

        foreach ($permissionCodes as $code) {
            $permissions->push(
                $this->createPermission($code)
            );
        }

        $role->permissions()->sync(
            $permissions->pluck('id')->all()
        );

        return $role;
    }

    private function createCompanyUser(
        array $context,
        Role $role,
        string $name,
        string $email
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'status' => 'activo',
            'password' => Hash::make('password123'),
        ]);

        $user->companies()->attach($context['company']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $user->branches()->attach($context['branch']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'company_id' => $context['company']->id,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        return $user;
    }

    private function createSuperAdmin(): User
    {
        $user = User::factory()->create([
            'name' => 'Super Admin Policy',
            'email' => 'superadmin-policy@test.local',
            'status' => 'activo',
            'password' => Hash::make('password123'),
        ]);

        $role = Role::create([
            'code' => 'super_admin',
            'name' => 'Super Admin',
            'description' => 'Rol global para pruebas de UserPolicy.',
            'status' => 'activo',
            'is_system' => true,
            'scope' => 'global',
        ]);

        $permissionCodes = [
            'usuarios.view',
            'usuarios.create',
            'usuarios.update',
            'usuarios.delete',
        ];

        $permissions = collect();

        foreach ($permissionCodes as $code) {
            $permissions->push(
                $this->createPermission($code)
            );
        }

        $role->permissions()->sync(
            $permissions->pluck('id')->all()
        );

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'company_id' => null,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        return $user;
    }

    private function setCompanyContext(
        Company $company
    ): void {
        request()->attributes->set(
            'company',
            $company
        );
    }

    private function createPolicyContext(): array
    {
        $companyContext1 = $this->createCompany('ONE');
        $companyContext2 = $this->createCompany('TWO');

        $company1 = $companyContext1['company'];
        $company2 = $companyContext2['company'];

        $permissions = [
            'usuarios.view',
            'usuarios.create',
            'usuarios.update',
            'usuarios.delete',
        ];

        $role1 = $this->createCompanyRole('ONE', $permissions);
        $role2 = $this->createCompanyRole('TWO', $permissions);

        $admin1 = $this->createCompanyUser(
            $companyContext1,
            $role1,
            'Administrador Policy 1',
            'admin-policy-1@test.local'
        );

        $admin2 = $this->createCompanyUser(
            $companyContext2,
            $role2,
            'Administrador Policy 2',
            'admin-policy-2@test.local'
        );

        $user1 = $this->createCompanyUser(
            $companyContext1,
            $role1,
            'Usuario Policy 1',
            'user-policy-1@test.local'
        );

        $user2 = $this->createCompanyUser(
            $companyContext2,
            $role2,
            'Usuario Policy 2',
            'user-policy-2@test.local'
        );

        $superAdmin = $this->createSuperAdmin();

        return [
            'company1' => $company1,
            'company2' => $company2,
            'admin1' => $admin1,
            'admin2' => $admin2,
            'user1' => $user1,
            'user2' => $user2,
            'superAdmin' => $superAdmin,
        ];
    }


    public function test_admin_can_view_user_from_same_company_using_request_context(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->view(
                $context['admin1'],
                $context['user1']
            )
        );
    }

    public function test_admin_cannot_view_user_from_other_company(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertFalse(
            $policy->view(
                $context['admin1'],
                $context['user2']
            )
        );
    }

    public function test_admin_cannot_view_user_from_other_company_even_when_context_is_that_other_company(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company2']);

        $policy = app(UserPolicy::class);

        $this->assertFalse(
            $policy->view(
                $context['admin1'],
                $context['user2']
            )
        );
    }

    public function test_super_admin_can_view_users_from_multiple_companies(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->view(
                $context['superAdmin'],
                $context['user1']
            )
        );

        $this->assertTrue(
            $policy->view(
                $context['superAdmin'],
                $context['user2']
            )
        );
    }

    public function test_view_any_uses_request_company_context(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->viewAny($context['admin1'])
        );
    }

    public function test_create_uses_request_company_context(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->create($context['admin1'])
        );
    }

    public function test_update_allows_user_to_update_themselves(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->update(
                $context['admin1'],
                $context['admin1']
            )
        );
    }

    public function test_update_allows_same_company_user(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->update(
                $context['admin1'],
                $context['user1']
            )
        );
    }

    public function test_update_denies_user_from_other_company(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertFalse(
            $policy->update(
                $context['admin1'],
                $context['user2']
            )
        );
    }

    public function test_super_admin_can_update_super_admin_target(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->update(
                $context['superAdmin'],
                $context['superAdmin']
            )
        );
    }

    public function test_admin_cannot_update_super_admin_target(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertFalse(
            $policy->update(
                $context['admin1'],
                $context['superAdmin']
            )
        );
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertFalse(
            $policy->delete(
                $context['admin1'],
                $context['admin1']
            )
        );
    }

    public function test_admin_can_delete_same_company_user(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->delete(
                $context['admin1'],
                $context['user1']
            )
        );
    }

    public function test_admin_cannot_delete_user_from_other_company(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertFalse(
            $policy->delete(
                $context['admin1'],
                $context['user2']
            )
        );
    }

    public function test_admin_cannot_delete_super_admin(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertFalse(
            $policy->delete(
                $context['admin1'],
                $context['superAdmin']
            )
        );
    }

    public function test_super_admin_can_delete_super_admin_target(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->delete(
                $context['superAdmin'],
                $context['admin1']
            )
        );
    }

    public function test_explicit_company_id_takes_precedence_over_request_context(): void
    {
        $context = $this->createPolicyContext();

        $this->setCompanyContext($context['company2']);

        $policy = app(UserPolicy::class);

        $this->assertTrue(
            $policy->view(
                $context['admin1'],
                $context['user1'],
                $context['company1']->id
            )
        );
    }

    public function test_missing_company_context_denies_company_scoped_user(): void
    {
        $context = $this->createPolicyContext();

        $policy = app(UserPolicy::class);

        $this->assertFalse(
            $policy->view(
                $context['admin1'],
                $context['user1']
            )
        );

        $this->assertFalse(
            $policy->viewAny($context['admin1'])
        );

        $this->assertFalse(
            $policy->create($context['admin1'])
        );
    }
}
