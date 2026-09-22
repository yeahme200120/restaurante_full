<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedContext(
        bool $withViewPermission = true,
        bool $withCreatePermission = true,
        bool $withUpdatePermission = false,
        bool $withDeletePermission = false
    ): array {
        $user = User::factory()->create([
            'status' => 'activo',
            'password' => Hash::make('password123'),
        ]);

        $company = Company::create([
            'name' => 'Empresa Usuarios Feature Test',
            'legal_name' => 'Empresa Usuarios Feature Test S.A. de C.V.',
            'tax_id' => 'USERS010101AAA',
            'email' => 'users@test.local',
            'phone' => '7350000000',
            'status' => 'activa',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Usuarios Test',
            'code' => 'USR-001',
            'address' => 'Direccion de prueba',
            'city' => 'Cuautla',
            'state' => 'Morelos',
            'postal_code' => '62700',
            'phone' => '7350000001',
            'email' => 'users-branch@test.local',
            'status' => 'activa',
            'is_main' => true,
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'USERS-LICENSE-' . $company->id,
            'plan' => 'pro',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'revoked_at' => null,
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
            'code' => 'admin_test_' . $user->id,
            'name' => 'Administrador Feature Test',
            'description' => 'Rol para pruebas de usuarios.',
            'status' => 'activo',
            'is_system' => false,
            'scope' => 'company',
        ]);

        $permissionCodes = [];

        if ($withViewPermission) {
            $permissionCodes[] = 'usuarios.view';
        }

        if ($withCreatePermission) {
            $permissionCodes[] = 'usuarios.create';
        }

        if ($withUpdatePermission) {
            $permissionCodes[] = 'usuarios.update';
        }

        if ($withDeletePermission) {
            $permissionCodes[] = 'usuarios.delete';
        }

        $permissions = collect();

        foreach ($permissionCodes as $code) {
            $permissions->push(
                Permission::firstOrCreate(
                    ['code' => $code],
                    [
                        'name' => $code,
                        'description' => 'Permiso ' . $code,
                        'module' => 'usuarios',
                        'section' => 'usuarios',
                        'action' => str_replace('usuarios.', '', $code),
                        'status' => 'activo',
                    ]
                )
            );
        }

        if ($permissions->isNotEmpty()) {
            $role->permissions()->sync(
                $permissions->pluck('id')->all()
            );
        }

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'company_id' => $company->id,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs(
            $user,
            ['api']
        );

        return [
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
            'role' => $role,
        ];
    }

    public function test_authenticated_user_with_permission_can_list_users(): void
    {
        $context = $this->createAuthenticatedContext();

        $targetUser = User::factory()->create([
            'name' => 'Usuario Objetivo',
            'email' => 'objetivo@test.local',
            'status' => 'activo',
        ]);

        $targetUser->companies()->attach($context['company']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $targetUser->branches()->attach($context['branch']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $response = $this->getJson('/api/v1/users');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ])
            ->assertJsonPath(
                'meta.total',
                2
            );

        $response->assertJsonFragment([
            'id' => $targetUser->id,
            'name' => 'Usuario Objetivo',
            'email' => 'objetivo@test.local',
        ]);
    }

    public function test_users_list_isolated_by_company(): void
    {
        $context = $this->createAuthenticatedContext();

        $otherCompany = Company::create([
            'name' => 'Otra Empresa',
            'status' => 'activa',
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'name' => 'Sucursal Otra Empresa',
            'code' => 'OTHER-001',
            'status' => 'activa',
            'is_main' => true,
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Usuario Otra Empresa',
            'email' => 'otra-empresa@test.local',
            'status' => 'activo',
        ]);

        $otherUser->companies()->attach($otherCompany->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $otherUser->branches()->attach($otherBranch->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $response = $this->getJson('/api/v1/users');

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                1
            )
            ->assertJsonMissing([
                'email' => 'otra-empresa@test.local',
            ]);

        $response->assertJsonFragment([
            'id' => $context['user']->id,
            'email' => $context['user']->email,
        ]);
    }

    public function test_users_list_supports_pagination(): void
    {
        $context = $this->createAuthenticatedContext();

        $targetUser = User::factory()->create([
            'name' => 'Usuario Paginado',
            'email' => 'paginado@test.local',
            'status' => 'activo',
        ]);

        $targetUser->companies()->attach($context['company']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $targetUser->branches()->attach($context['branch']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $response = $this->getJson(
            '/api/v1/users?per_page=1&page=2'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.current_page',
                2
            )
            ->assertJsonPath(
                'meta.per_page',
                1
            )
            ->assertJsonPath(
                'meta.total',
                2
            )
            ->assertJsonPath(
                'meta.last_page',
                2
            );
    }

    public function test_users_list_caps_per_page_at_one_hundred(): void
    {
        $this->createAuthenticatedContext();

        $response = $this->getJson(
            '/api/v1/users?per_page=500'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.per_page',
                100
            );
    }

    public function test_user_can_be_created_with_company_branch_and_role(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: true
        );

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Usuario Creado Feature',
            'email' => 'usuario-creado-feature@test.local',
            'phone' => '7350000010',
            'password' => 'PasswordTest123!',
            'status' => 'activo',
            'branch_id' => $context['branch']->id,
            'role_id' => $context['role']->id,
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'message' => 'Usuario creado correctamente.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'status',
                ],
            ]);

        $createdUser = User::where(
            'email',
            'usuario-creado-feature@test.local'
        )->first();

        $this->assertNotNull($createdUser);

        $this->assertDatabaseHas('user_company_assignments', [
            'user_id' => $createdUser->id,
            'company_id' => $context['company']->id,
            'is_default' => true,
            'status' => 'activa',
        ]);

        $this->assertDatabaseHas('user_branch_assignments', [
            'user_id' => $createdUser->id,
            'branch_id' => $context['branch']->id,
            'is_default' => true,
            'status' => 'activa',
        ]);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $createdUser->id,
            'role_id' => $context['role']->id,
            'company_id' => $context['company']->id,
            'status' => 'activo',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'module' => 'usuarios',
            'result' => 'success',
            'user_id' => $context['user']->id,
            'company_id' => $context['company']->id,
            'record_type' => User::class,
            'record_id' => $createdUser->id,
        ]);
    }

    public function test_user_creation_rejects_super_admin_role(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: true
        );

        $superAdminRole = Role::create([
            'code' => 'super_admin',
            'name' => 'Super Admin',
            'description' => 'Rol global para pruebas.',
            'status' => 'activo',
            'is_system' => true,
            'scope' => 'global',
        ]);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Super Admin No Permitido',
            'email' => 'super-admin-no-permitido@test.local',
            'phone' => '7350000011',
            'password' => 'PasswordTest123!',
            'status' => 'activo',
            'branch_id' => $context['branch']->id,
            'role_id' => $superAdminRole->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' =>
                'El rol seleccionado no puede asignarse a un usuario de empresa.',
            ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'super-admin-no-permitido@test.local',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.create_failed',
            'module' => 'usuarios',
            'result' => 'error',
            'error_code' => 'USER_CREATE_FAILED',
            'user_id' => $context['user']->id,
            'company_id' => $context['company']->id,
            'record_id' => null,
        ]);
    }

    public function test_user_creation_requires_create_permission(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: true,
            withCreatePermission: false
        );

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Sin Permiso',
            'email' => 'sin-permiso@test.local',
            'password' => 'PasswordTest123!',
            'status' => 'activo',
            'branch_id' => $context['branch']->id,
            'role_id' => $context['role']->id,
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'sin-permiso@test.local',
        ]);
    }

    public function test_users_list_requires_view_permission(): void
    {
        $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: true
        );

        $response = $this->getJson('/api/v1/users');

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_unauthenticated_user_cannot_list_users(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertUnauthorized();
    }

    public function test_user_can_be_updated_with_company_scope(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: false,
            withUpdatePermission: true
        );

        $targetUser = User::factory()->create([
            'name' => 'Usuario Original',
            'email' => 'usuario-original@test.local',
            'phone' => '7350000020',
            'status' => 'activo',
        ]);

        $targetUser->companies()->attach($context['company']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $targetUser->branches()->attach($context['branch']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        UserRole::create([
            'user_id' => $targetUser->id,
            'role_id' => $context['role']->id,
            'company_id' => $context['company']->id,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        $response = $this->putJson(
            '/api/v1/users/' . $targetUser->id,
            [
                'name' => 'Usuario Actualizado',
                'phone' => '7350000099',
                'status' => 'inactivo',
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Usuario actualizado correctamente.',
                'data' => [
                    'id' => $targetUser->id,
                    'name' => 'Usuario Actualizado',
                    'email' => 'usuario-original@test.local',
                    'phone' => '7350000099',
                    'status' => 'inactivo',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Usuario Actualizado',
            'phone' => '7350000099',
            'status' => 'inactivo',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'module' => 'usuarios',
            'result' => 'success',
            'user_id' => $context['user']->id,
            'company_id' => $context['company']->id,
            'record_type' => User::class,
            'record_id' => $targetUser->id,
        ]);
    }

    public function test_user_can_update_branch_and_role(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: false,
            withUpdatePermission: true
        );

        $newBranch = Branch::create([
            'company_id' => $context['company']->id,
            'name' => 'Nueva Sucursal Test',
            'code' => 'USR-002',
            'address' => 'Nueva direccion',
            'city' => 'Cuautla',
            'state' => 'Morelos',
            'postal_code' => '62700',
            'phone' => '7350000002',
            'email' => 'users-branch-2@test.local',
            'status' => 'activa',
            'is_main' => false,
        ]);

        $context['user']->branches()->attach($newBranch->id, [
            'is_default' => false,
            'status' => 'activa',
        ]);

        $newRole = Role::create([
            'code' => 'cajero_test_' . $context['user']->id,
            'name' => 'Cajero Feature Test',
            'description' => 'Rol de cajero para pruebas.',
            'status' => 'activo',
            'is_system' => false,
            'scope' => 'company',
        ]);

        $targetUser = User::factory()->create([
            'name' => 'Usuario Para Cambio',
            'email' => 'usuario-cambio@test.local',
            'status' => 'activo',
        ]);

        $targetUser->companies()->attach($context['company']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $targetUser->branches()->attach($context['branch']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        UserRole::create([
            'user_id' => $targetUser->id,
            'role_id' => $context['role']->id,
            'company_id' => $context['company']->id,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        $response = $this->patchJson(
            '/api/v1/users/' . $targetUser->id,
            [
                'branch_id' => $newBranch->id,
                'role_id' => $newRole->id,
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Usuario actualizado correctamente.',
            ]);

        $this->assertDatabaseHas('user_branch_assignments', [
            'user_id' => $targetUser->id,
            'branch_id' => $newBranch->id,
            'is_default' => true,
            'status' => 'activa',
        ]);

        $this->assertDatabaseHas('user_branch_assignments', [
            'user_id' => $targetUser->id,
            'branch_id' => $context['branch']->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $targetUser->id,
            'role_id' => $newRole->id,
            'company_id' => $context['company']->id,
            'status' => 'activo',
        ]);
    }

    public function test_user_update_cannot_modify_user_from_other_company(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: false,
            withUpdatePermission: true
        );

        $otherCompany = Company::create([
            'name' => 'Empresa Update Externa',
            'legal_name' => 'Empresa Update Externa S.A. de C.V.',
            'tax_id' => 'UPDATE010101AAA',
            'email' => 'update-other@test.local',
            'status' => 'activa',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Usuario Otra Empresa',
            'email' => 'usuario-update-externo@test.local',
            'status' => 'activo',
        ]);

        $otherUser->companies()->attach($otherCompany->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $response = $this->putJson(
            '/api/v1/users/' . $otherUser->id,
            [
                'name' => 'Intento de Modificacion',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'El usuario no pertenece a la empresa activa.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'name' => 'Usuario Otra Empresa',
        ]);
    }

    public function test_user_update_rejects_super_admin_role(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: false,
            withUpdatePermission: true
        );

        $superAdminRole = Role::create([
            'code' => 'super_admin',
            'name' => 'Super Admin',
            'description' => 'Rol global para pruebas.',
            'status' => 'activo',
            'is_system' => true,
            'scope' => 'global',
        ]);

        $targetUser = User::factory()->create([
            'name' => 'Usuario Normal',
            'email' => 'usuario-normal-update@test.local',
            'status' => 'activo',
        ]);

        $targetUser->companies()->attach($context['company']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $targetUser->branches()->attach($context['branch']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        UserRole::create([
            'user_id' => $targetUser->id,
            'role_id' => $context['role']->id,
            'company_id' => $context['company']->id,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        $response = $this->putJson(
            '/api/v1/users/' . $targetUser->id,
            [
                'role_id' => $superAdminRole->id,
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' =>
                'El rol seleccionado no puede asignarse a un usuario de empresa.',
            ]);

        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $targetUser->id,
            'role_id' => $superAdminRole->id,
        ]);
    }

    public function test_user_update_requires_update_permission(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: true,
            withCreatePermission: false,
            withUpdatePermission: false
        );

        $targetUser = User::factory()->create([
            'name' => 'Usuario Sin Permiso',
            'email' => 'usuario-sin-update@test.local',
            'status' => 'activo',
        ]);

        $targetUser->companies()->attach($context['company']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $targetUser->branches()->attach($context['branch']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $response = $this->putJson(
            '/api/v1/users/' . $targetUser->id,
            [
                'name' => 'No Debe Actualizar',
            ]
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Usuario Sin Permiso',
        ]);
    }

    public function test_user_can_be_deleted_from_same_company(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: false,
            withUpdatePermission: false,
            withDeletePermission: true
        );

        $targetUser = User::factory()->create([
            'name' => 'Usuario Para Eliminar',
            'email' => 'usuario-eliminar@test.local',
            'status' => 'activo',
        ]);

        $targetUser->companies()->attach($context['company']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $targetUser->branches()->attach($context['branch']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        UserRole::create([
            'user_id' => $targetUser->id,
            'role_id' => $context['role']->id,
            'company_id' => $context['company']->id,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        $response = $this->deleteJson(
            '/api/v1/users/' . $targetUser->id
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Usuario eliminado correctamente.',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);

        $this->assertDatabaseMissing('user_company_assignments', [
            'user_id' => $targetUser->id,
        ]);

        $this->assertDatabaseMissing('user_branch_assignments', [
            'user_id' => $targetUser->id,
        ]);

        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $targetUser->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'module' => 'usuarios',
            'result' => 'success',
            'user_id' => $context['user']->id,
            'company_id' => $context['company']->id,
            'record_type' => User::class,
            'record_id' => $targetUser->id,
        ]);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: false,
            withUpdatePermission: false,
            withDeletePermission: true
        );

        $response = $this->deleteJson(
            '/api/v1/users/' . $context['user']->id
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $context['user']->id,
        ]);
    }

    public function test_user_cannot_delete_user_from_other_company(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: false,
            withUpdatePermission: false,
            withDeletePermission: true
        );

        $otherCompany = Company::create([
            'name' => 'Empresa Delete Externa',
            'legal_name' => 'Empresa Delete Externa S.A. de C.V.',
            'tax_id' => 'DELETE010101AAA',
            'email' => 'delete-other@test.local',
            'status' => 'activa',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Usuario Delete Otra Empresa',
            'email' => 'usuario-delete-externo@test.local',
            'status' => 'activo',
        ]);

        $otherUser->companies()->attach($otherCompany->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $response = $this->deleteJson(
            '/api/v1/users/' . $otherUser->id
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'email' => 'usuario-delete-externo@test.local',
        ]);
    }

    public function test_user_delete_requires_delete_permission(): void
    {
        $context = $this->createAuthenticatedContext(
            withViewPermission: false,
            withCreatePermission: false,
            withUpdatePermission: false,
            withDeletePermission: false
        );

        $targetUser = User::factory()->create([
            'name' => 'Usuario Sin Permiso Delete',
            'email' => 'usuario-sin-delete@test.local',
            'status' => 'activo',
        ]);

        $targetUser->companies()->attach($context['company']->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $response = $this->deleteJson(
            '/api/v1/users/' . $targetUser->id
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'email' => 'usuario-sin-delete@test.local',
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_user(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Usuario Delete Sin Auth',
            'email' => 'usuario-delete-sin-auth@test.local',
            'status' => 'activo',
        ]);

        $response = $this->deleteJson(
            '/api/v1/users/' . $targetUser->id
        );

        $response->assertUnauthorized();

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'email' => 'usuario-delete-sin-auth@test.local',
        ]);
    }
}