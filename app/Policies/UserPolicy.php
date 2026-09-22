<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use Illuminate\Http\Request;

class UserPolicy
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Determina si el usuario puede ver cualquier usuario.
     */
    public function viewAny(
        User $user,
        ?int $companyId = null
    ): bool {
        $companyId = $this->resolveCompanyId($companyId);

        return $this->authorizationService->hasPermission(
            $user,
            'usuarios.view',
            $companyId
        );
    }

    /**
     * Determina si el usuario puede ver un usuario específico.
     */
    public function view(
        User $user,
        User $targetUser,
        ?int $companyId = null
    ): bool {
        $companyId = $this->resolveCompanyId($companyId);

        if (! $this->authorizationService->hasPermission(
            $user,
            'usuarios.view',
            $companyId
        )) {
            return false;
        }

        return $this->sameCompanyScope(
            $user,
            $targetUser,
            $companyId
        );
    }

    /**
     * Determina si el usuario puede crear usuarios.
     */
    public function create(
        User $user,
        ?int $companyId = null
    ): bool {
        $companyId = $this->resolveCompanyId($companyId);

        return $this->authorizationService->hasPermission(
            $user,
            'usuarios.create',
            $companyId
        );
    }

    /**
     * Determina si el usuario puede actualizar un usuario.
     */
    public function update(
        User $user,
        User $targetUser,
        ?int $companyId = null
    ): bool {
        $companyId = $this->resolveCompanyId($companyId);

        if (! $this->authorizationService->hasPermission(
            $user,
            'usuarios.update',
            $companyId
        )) {
            return false;
        }

        if ($user->id === $targetUser->id) {
            return true;
        }

        if ($this->targetIsSuperAdmin($targetUser)) {
            return $this->isSuperAdmin($user);
        }

        return $this->sameCompanyScope(
            $user,
            $targetUser,
            $companyId
        );
    }

    /**
     * Determina si el usuario puede eliminar un usuario.
     */
    public function delete(
        User $user,
        User $targetUser,
        ?int $companyId = null
    ): bool {
        $companyId = $this->resolveCompanyId($companyId);

        if (! $this->authorizationService->hasPermission(
            $user,
            'usuarios.delete',
            $companyId
        )) {
            return false;
        }

        if ($user->id === $targetUser->id) {
            return false;
        }

        if ($this->targetIsSuperAdmin($targetUser)) {
            return $this->isSuperAdmin($user);
        }

        return $this->sameCompanyScope(
            $user,
            $targetUser,
            $companyId
        );
    }

    /**
     * Resuelve el ID de empresa para la Policy.
     *
     * Prioridad:
     * 1. ID de empresa recibido explícitamente.
     * 2. Empresa establecida por ValidateCompanyAccess.
     * 3. null si no existe contexto.
     */
    protected function resolveCompanyId(?int $companyId = null): ?int
    {
        if ($companyId !== null) {
            return $companyId;
        }

        $company = request()->attributes->get('company');

        if ($company !== null) {
            return $company->id;
        }

        return null;
    }

    /**
     * Comprueba si el usuario autenticado es Super Admin.
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $this->authorizationService->hasRole(
            $user,
            'super_admin'
        );
    }

    /**
     * Comprueba si el usuario objetivo tiene rol Super Admin.
     */
    protected function targetIsSuperAdmin(User $targetUser): bool
    {
        return $targetUser->userRoles()
            ->where('user_roles.status', 'activo')
            ->whereHas('role', function ($query) {
                $query
                    ->where('code', 'super_admin')
                    ->where('scope', 'global')
                    ->where('status', 'activo');
            })
            ->exists();
    }

    /**
     * Comprueba que el usuario objetivo pertenezca
     * a la misma empresa dentro del contexto actual.
     */
    protected function sameCompanyScope(
        User $user,
        User $targetUser,
        ?int $companyId
    ): bool {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($companyId === null) {
            return false;
        }

        return $targetUser->companies()
            ->where('companies.id', $companyId)
            ->wherePivot('status', 'activa')
            ->exists();
    }
}
