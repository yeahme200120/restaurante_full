<?php

namespace Tests\Unit\Services\Company;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\Role;
use App\Models\User;
use App\Services\Company\CompanyAccessService;
use App\Services\Company\CompanyContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_company_with_valid_license_allows_user_access(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $company = Company::create([
            'name' => 'Empresa de prueba',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'LIC-TEST-001',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $service = app(CompanyAccessService::class);

        $this->assertTrue(
            $service->userCanAccessCompany($user, $company)
        );
    }

    public function test_suspended_company_denies_access(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $company = Company::create([
            'name' => 'Empresa suspendida',
            'status' => 'suspendida',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'LIC-TEST-002',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $service = app(CompanyAccessService::class);

        $this->assertFalse(
            $service->userCanAccessCompany($user, $company)
        );
    }

    public function test_expired_license_denies_access(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $company = Company::create([
            'name' => 'Empresa licencia vencida',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'LIC-TEST-003',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $service = app(CompanyAccessService::class);

        $this->assertFalse(
            $service->userCanAccessCompany($user, $company)
        );
    }

    public function test_user_without_company_assignment_is_denied(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $company = Company::create([
            'name' => 'Empresa sin usuario',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'LIC-TEST-004',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $service = app(CompanyAccessService::class);

        $this->assertFalse(
            $service->userCanAccessCompany($user, $company)
        );
    }

    public function test_context_resolves_authorized_company_and_branch(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $company = Company::create([
            'name' => 'Empresa principal',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'LIC-TEST-005',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal principal',
            'code' => 'MAIN',
            'status' => 'activa',
            'is_main' => true,
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $user->branches()->attach($branch->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $service = app(CompanyContextService::class);

        $context = $service->resolve($user);

        $this->assertSame($company->id, $context['company']->id);
        $this->assertSame($branch->id, $context['branch']->id);
    }

    public function test_branch_from_another_company_is_not_authorized(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $company = Company::create([
            'name' => 'Empresa autorizada',
            'status' => 'activa',
        ]);

        $otherCompany = Company::create([
            'name' => 'Otra empresa',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'LIC-TEST-006',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        CompanyLicense::create([
            'company_id' => $otherCompany->id,
            'license_key' => 'LIC-TEST-007',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $authorizedBranch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal autorizada',
            'code' => 'MAIN',
            'status' => 'activa',
            'is_main' => true,
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'name' => 'Sucursal externa',
            'code' => 'OTHER',
            'status' => 'activa',
            'is_main' => true,
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $user->branches()->attach($authorizedBranch->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $user->branches()->attach($otherBranch->id, [
            'is_default' => false,
            'status' => 'activa',
        ]);

        $service = app(CompanyContextService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'No existe una sucursal activa autorizada para el usuario.'
        );

        $service->resolve(
            $user,
            $company->id,
            $otherBranch->id
        );
    }

    public function test_global_super_admin_can_access_company_without_company_assignment(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $role = Role::create([
            'code' => 'super_admin',
            'name' => 'Super Admin',
            'description' => 'Administrador global del sistema.',
            'status' => 'activo',
            'is_system' => true,
            'scope' => 'global',
        ]);

        $user->userRoles()->create([
            'role_id' => $role->id,
            'company_id' => null,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        $company = Company::create([
            'name' => 'Empresa Super Admin',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'LIC-TEST-SUPER-001',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $this->assertFalse(
            $user->companies()->where('companies.id', $company->id)->exists()
        );

        $service = app(CompanyAccessService::class);

        $this->assertTrue(
            $service->userCanAccessCompany($user, $company)
        );
    }

    public function test_global_super_admin_can_resolve_company_and_branch_but_not_cross_company_branch(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $role = Role::create([
            'code' => 'super_admin',
            'name' => 'Super Admin',
            'description' => 'Administrador global del sistema.',
            'status' => 'activo',
            'is_system' => true,
            'scope' => 'global',
        ]);

        $user->userRoles()->create([
            'role_id' => $role->id,
            'company_id' => null,
            'status' => 'activo',
            'assigned_at' => now(),
        ]);

        $company = Company::create([
            'name' => 'Empresa Uno',
            'status' => 'activa',
        ]);

        $otherCompany = Company::create([
            'name' => 'Empresa Dos',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'LIC-TEST-SUPER-002',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        CompanyLicense::create([
            'company_id' => $otherCompany->id,
            'license_key' => 'LIC-TEST-SUPER-003',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Uno',
            'code' => 'SUPER-001',
            'status' => 'activa',
            'is_main' => true,
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'name' => 'Sucursal Dos',
            'code' => 'SUPER-002',
            'status' => 'activa',
            'is_main' => true,
        ]);

        $service = app(CompanyContextService::class);

        $resolvedCompany = $service->resolveCompany(
            $user,
            $company->id
        );

        $resolvedOtherCompany = $service->resolveCompany(
            $user,
            $otherCompany->id
        );

        $this->assertSame($company->id, $resolvedCompany->id);
        $this->assertSame($otherCompany->id, $resolvedOtherCompany->id);

        $resolvedBranch = $service->resolveBranch(
            $user,
            $company,
            $branch->id
        );

        $this->assertSame($branch->id, $resolvedBranch->id);
        $this->assertSame($company->id, $resolvedBranch->company_id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'No existe una sucursal activa autorizada para el usuario.'
        );

        $service->resolveBranch(
            $user,
            $company,
            $otherBranch->id
        );
    }
}