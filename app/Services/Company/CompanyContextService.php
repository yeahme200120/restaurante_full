<?php

namespace App\Services\Company;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use RuntimeException;

class CompanyContextService
{
    public function __construct(
        protected CompanyAccessService $accessService
    ) {}

    public function resolveCompany(
        User $user,
        ?int $companyId = null
    ): Company {
        $query = $user->companies()
            ->wherePivot('status', 'activa')
            ->where('companies.status', 'activa');

        if ($companyId !== null) {
            $query->where('companies.id', $companyId);
        } else {
            $query->wherePivot('is_default', true);
        }

        $company = $query->first();

        if (! $company) {
            throw new RuntimeException(
                'No existe una empresa activa autorizada para el usuario.'
            );
        }

        if (! $this->accessService->companyHasValidLicense($company)) {
            throw new RuntimeException(
                'La empresa no tiene una licencia válida.'
            );
        }

        return $company;
    }

    public function resolveBranch(
        User $user,
        Company $company,
        ?int $branchId = null
    ): Branch {
        $query = $user->branches()
            ->wherePivot('status', 'activa')
            ->where('branches.company_id', $company->id)
            ->where('branches.status', 'activa');

        if ($branchId !== null) {
            $query->where('branches.id', $branchId);
        } else {
            $query->wherePivot('is_default', true);
        }

        $branch = $query->first();

        if (! $branch) {
            throw new RuntimeException(
                'No existe una sucursal activa autorizada para el usuario.'
            );
        }

        return $branch;
    }

    public function resolve(
        User $user,
        ?int $companyId = null,
        ?int $branchId = null
    ): array {
        if (! $this->accessService->userIsActive($user)) {
            throw new RuntimeException(
                'El usuario no está activo.'
            );
        }

        $company = $this->resolveCompany(
            $user,
            $companyId
        );

        if (! $this->accessService->userCanAccessCompany(
            $user,
            $company
        )) {
            throw new RuntimeException(
                'El usuario no tiene acceso a la empresa.'
            );
        }

        $branch = $this->resolveBranch(
            $user,
            $company,
            $branchId
        );

        return [
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
        ];
    }
}
