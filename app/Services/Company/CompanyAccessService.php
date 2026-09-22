<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use Carbon\Carbon;

class CompanyAccessService
{
    public function userCanAccessCompany(User $user, Company $company): bool
    {
        if (! $this->userIsActive($user)) {
            return false;
        }

        if (! $this->companyIsActive($company)) {
            return false;
        }

        if (! $this->userBelongsToCompany($user, $company)) {
            return false;
        }

        return $this->companyHasValidLicense($company);
    }

    public function companyIsActive(Company $company): bool
    {
        return $company->status === 'activa';
    }

    public function userIsActive(User $user): bool
    {
        return $user->status === 'activo';
    }

    public function userBelongsToCompany(
        User $user,
        Company $company
    ): bool {
        if (app(AuthorizationService::class)->hasRole($user, 'super_admin')) {
            return true;
        }

        return $user->companies()
            ->where('companies.id', $company->id)
            ->wherePivot('status', 'activa')
            ->exists();
    }

    public function companyHasValidLicense(Company $company): bool
    {
        $now = Carbon::now();

        return $company->licenses()
            ->where('status', 'activa')
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            })
            ->whereNull('revoked_at')
            ->exists();
    }

    public function getActiveLicense(
        Company $company
    ): ?CompanyLicense {
        $now = Carbon::now();

        return $company->licenses()
            ->where('status', 'activa')
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            })
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();
    }
}