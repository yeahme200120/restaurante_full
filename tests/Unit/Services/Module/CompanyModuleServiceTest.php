<?php

namespace Tests\Unit\Services\Module;

use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Services\Audit\AuditService;
use App\Services\Module\CompanyModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyModuleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CompanyModuleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CompanyModuleService::class);
    }

    public function test_it_enables_a_module_for_a_company(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();

        $companyModule = $this->service->enable(
            $company,
            $module
        );

        $this->assertInstanceOf(
            CompanyModule::class,
            $companyModule
        );

        $this->assertDatabaseHas('company_modules', [
            'company_id' => $company->id,
            'module_id' => $module->id,
            'status' => 'activo',
        ]);

        $this->assertNotNull($companyModule->enabled_at);
        $this->assertNull($companyModule->disabled_at);
    }

    public function test_it_does_not_create_duplicate_when_module_is_already_enabled(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();

        $first = $this->service->enable(
            $company,
            $module
        );

        $second = $this->service->enable(
            $company,
            $module
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertDatabaseCount('company_modules', 1);
    }

    public function test_it_disables_an_enabled_module(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();

        $companyModule = $this->service->enable(
            $company,
            $module
        );

        $result = $this->service->disable(
            $company,
            $module
        );

        $this->assertSame(
            $companyModule->id,
            $result->id
        );

        $this->assertDatabaseHas('company_modules', [
            'id' => $companyModule->id,
            'status' => 'inactivo',
        ]);

        $this->assertNotNull($result->disabled_at);
    }

    public function test_it_rejects_disabling_a_module_that_is_not_enabled(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'El módulo no está habilitado para la empresa.'
        );

        $this->service->disable(
            $company,
            $module
        );
    }

    public function test_it_reports_whether_a_module_is_enabled(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();

        $this->assertFalse(
            $this->service->isEnabled(
                $company,
                $module
            )
        );

        $this->service->enable(
            $company,
            $module
        );

        $this->assertTrue(
            $this->service->isEnabled(
                $company,
                $module
            )
        );

        $this->service->disable(
            $company,
            $module
        );

        $this->assertFalse(
            $this->service->isEnabled(
                $company,
                $module
            )
        );
    }

    public function test_it_returns_only_enabled_modules(): void
    {
        $company = Company::factory()->create();

        $enabledModule = Module::factory()->create([
            'sort_order' => 10,
        ]);

        $disabledModule = Module::factory()->create([
            'sort_order' => 20,
        ]);

        $inactiveGlobalModule = Module::factory()->create([
            'status' => 'inactivo',
            'sort_order' => 30,
        ]);

        $this->service->enable(
            $company,
            $enabledModule
        );

        $this->service->enable(
            $company,
            $disabledModule
        );

        $this->service->disable(
            $company,
            $disabledModule
        );

        $result = $this->service->getEnabledModules(
            $company
        );

        $this->assertCount(1, $result);

        $this->assertTrue(
            $result->contains(
                fn (Module $module) => $module->id === $enabledModule->id
            )
        );

        $this->assertFalse(
            $result->contains(
                fn (Module $module) => $module->id === $disabledModule->id
            )
        );

        $this->assertFalse(
            $result->contains(
                fn (Module $module) => $module->id === $inactiveGlobalModule->id
            )
        );
    }

    public function test_it_rejects_an_inactive_company(): void
    {
        $company = Company::factory()
            ->inactive()
            ->create();

        $module = Module::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'La empresa no está activa.'
        );

        $this->service->enable(
            $company,
            $module
        );
    }

    public function test_it_rejects_an_inactive_global_module(): void
    {
        $company = Company::factory()->create();

        $module = Module::factory()->create([
            'status' => 'inactivo',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'El módulo no está activo en el catálogo global.'
        );

        $this->service->enable(
            $company,
            $module
        );
    }

    public function test_it_returns_a_company_module_configuration(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();

        $this->assertNull(
            $this->service->getCompanyModule(
                $company,
                $module
            )
        );

        $created = $this->service->enable(
            $company,
            $module
        );

        $result = $this->service->getCompanyModule(
            $company,
            $module
        );

        $this->assertNotNull($result);

        $this->assertSame(
            $created->id,
            $result->id
        );
    }

    public function test_it_reenables_a_disabled_module_without_creating_a_duplicate(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();

        $created = $this->service->enable(
            $company,
            $module
        );

        $this->service->disable(
            $company,
            $module
        );

        $reenabled = $this->service->enable(
            $company,
            $module
        );

        $this->assertSame(
            $created->id,
            $reenabled->id
        );

        $this->assertSame(
            'activo',
            $reenabled->status
        );

        $this->assertNull(
            $reenabled->disabled_at
        );

        $this->assertNotNull(
            $reenabled->enabled_at
        );

        $this->assertDatabaseCount(
            'company_modules',
            1
        );
    }

    public function test_it_records_audit_when_module_is_enabled(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();

        $this->service->enable(
            $company,
            $module
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'module.enabled',
            'company_id' => $company->id,
        ]);
    }

    public function test_it_records_audit_when_module_is_disabled(): void
    {
        $company = Company::factory()->create();
        $module = Module::factory()->create();

        $this->service->enable(
            $company,
            $module
        );

        $this->service->disable(
            $company,
            $module
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'module.disabled',
            'company_id' => $company->id,
        ]);
    }
}