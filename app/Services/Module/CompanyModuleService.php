<?php

namespace App\Services\Module;

use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CompanyModuleService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function enable(
        Company $company,
        Module $module,
        ?int $userId = null,
        ?int $roleId = null
    ): CompanyModule {
        try {
            return DB::transaction(function () use (
                $company,
                $module,
                $userId,
                $roleId
            ) {
                $this->validateCompany($company);
                $this->validateModule($module);

                $companyModule = CompanyModule::query()
                    ->where('company_id', $company->id)
                    ->where('module_id', $module->id)
                    ->first();

                $oldValues = $companyModule?->toArray();

                if (
                    $companyModule
                    && $companyModule->status === 'activo'
                    && $companyModule->disabled_at === null
                ) {
                    return $companyModule;
                }

                if (! $companyModule) {
                    $companyModule = new CompanyModule();
                    $companyModule->company_id = $company->id;
                    $companyModule->module_id = $module->id;
                }

                $companyModule->status = 'activo';
                $companyModule->enabled_at = now();
                $companyModule->disabled_at = null;
                $companyModule->save();

                $this->auditService->success(
                    action: 'module.enabled',
                    model: $companyModule,
                    oldValues: $oldValues,
                    newValues: $companyModule->toArray(),
                    module: $module->code,
                    userId: $userId,
                    roleId: $roleId,
                    companyId: $company->id
                );

                return $companyModule->fresh([
                    'company',
                    'module',
                ]);
            });
        } catch (Throwable $exception) {
            $this->auditService->failure(
                action: 'module.enable_failed',
                model: null,
                oldValues: null,
                newValues: [
                    'company_id' => $company->id,
                    'module_id' => $module->id,
                ],
                module: $module->code,
                errorCode: 'MODULE_ENABLE_ERROR',
                exception: $exception,
                userId: $userId,
                roleId: $roleId,
                companyId: $company->id
            );

            throw $exception;
        }
    }

    public function disable(
        Company $company,
        Module $module,
        ?int $userId = null,
        ?int $roleId = null
    ): CompanyModule {
        try {
            return DB::transaction(function () use (
                $company,
                $module,
                $userId,
                $roleId
            ) {
                $this->validateCompany($company);
                $this->validateModule($module);

                $companyModule = CompanyModule::where(
                    'company_id',
                    $company->id
                )
                    ->where('module_id', $module->id)
                    ->first();

                if (! $companyModule) {
                    throw new RuntimeException(
                        'El módulo no está habilitado para la empresa.'
                    );
                }

                $oldValues = $companyModule->toArray();

                if ($companyModule->status === 'inactivo') {
                    return $companyModule;
                }

                $companyModule->status = 'inactivo';
                $companyModule->disabled_at = now();
                $companyModule->save();

                $this->auditService->success(
                    action: 'module.disabled',
                    model: $companyModule,
                    oldValues: $oldValues,
                    newValues: $companyModule->toArray(),
                    module: $module->code,
                    userId: $userId,
                    roleId: $roleId,
                    companyId: $company->id
                );

                return $companyModule->fresh([
                    'company',
                    'module',
                ]);
            });
        } catch (Throwable $exception) {
            $this->auditService->failure(
                action: 'module.disable_failed',
                model: null,
                oldValues: null,
                newValues: [
                    'company_id' => $company->id,
                    'module_id' => $module->id,
                ],
                module: $module->code,
                errorCode: 'MODULE_DISABLE_ERROR',
                exception: $exception,
                userId: $userId,
                roleId: $roleId,
                companyId: $company->id
            );

            throw $exception;
        }
    }

    public function isEnabled(
        Company $company,
        Module $module
    ): bool {
        return CompanyModule::query()
            ->where('company_id', $company->id)
            ->where('module_id', $module->id)
            ->where('status', 'activo')
            ->whereNull('disabled_at')
            ->exists();
    }

    public function getEnabledModules(Company $company)
    {
        return $company->modules()
            ->where('modules.status', 'activo')
            ->wherePivot('status', 'activo')
            ->wherePivotNull('disabled_at')
            ->orderBy('modules.sort_order')
            ->get();
    }

    public function getCompanyModule(
        Company $company,
        Module $module
    ): ?CompanyModule {
        return CompanyModule::query()
            ->where('company_id', $company->id)
            ->where('module_id', $module->id)
            ->first();
    }

    protected function validateCompany(Company $company): void
    {
        if (! $company->exists) {
            throw new RuntimeException(
                'La empresa no existe.'
            );
        }

        if ($company->status !== 'activo') {
            throw new RuntimeException(
                'La empresa no está activa.'
            );
        }
    }

    protected function validateModule(Module $module): void
    {
        if (! $module->exists) {
            throw new RuntimeException(
                'El módulo no existe.'
            );
        }

        if ($module->status !== 'activo') {
            throw new RuntimeException(
                'El módulo no está activo en el catálogo global.'
            );
        }
    }
}

