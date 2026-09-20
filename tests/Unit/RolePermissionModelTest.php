<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_can_have_permissions(): void
    {
        $role = Role::factory()->create();

        $permission = Permission::factory()->create();

        $role->permissions()->attach($permission->id);

        $this->assertTrue(
            $role->permissions()
                ->whereKey($permission->id)
                ->exists()
        );

        $this->assertTrue(
            $permission->roles()
                ->whereKey($role->id)
                ->exists()
        );
    }

    public function test_user_can_have_company_role(): void
    {
        $user = User::factory()->create();

        $company = Company::factory()->create();

        $role = Role::factory()->create([
            'scope' => 'company',
        ]);

        $userRole = UserRole::factory()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'company_id' => $company->id,
        ]);

        $this->assertTrue(
            $user->userRoles()
                ->whereKey($userRole->id)
                ->exists()
        );

        $this->assertTrue(
            $user->roles()
                ->whereKey($role->id)
                ->wherePivot('company_id', $company->id)
                ->exists()
        );
    }

    public function test_user_can_have_global_role_without_company(): void
    {
        $user = User::factory()->create();

        $role = Role::factory()
            ->global()
            ->system()
            ->create([
                'code' => 'super_admin',
                'name' => 'Super Admin',
            ]);

        $userRole = UserRole::factory()
            ->global()
            ->create([
                'user_id' => $user->id,
                'role_id' => $role->id,
            ]);

        $this->assertNull($userRole->company_id);

        $this->assertTrue(
            $user->roles()
                ->whereKey($role->id)
                ->whereNull('user_roles.company_id')
                ->exists()
        );
    }

    public function test_role_permissions_do_not_allow_duplicates(): void
    {
        $role = Role::factory()->create();

        $permission = Permission::factory()->create();

        $role->permissions()->attach($permission->id);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $role->permissions()->attach($permission->id);
    }
}