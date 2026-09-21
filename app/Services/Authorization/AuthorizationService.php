<?php

namespace App\Services\Authorization;

use App\Models\Permission;
use App\Models\User;
use RuntimeException;

class AuthorizationService
{
    /**
     * Determina si el usuario tiene un rol activo.
     *
     * Los roles globales no requieren company_id.
     * Los roles de empresa deben estar asignados a la empresa indicada.
     */
    public function hasRole(
        User $user,
        string $roleCode,
        ?int $companyId = null
    ): bool {
        if ($user->status !== 'activo') {
            return false;
        }

        return $user->userRoles()
            ->where('user_roles.status', 'activo')
            ->whereHas('role', function ($query) use ($roleCode) {
                $query
                    ->where('code', $roleCode)
                    ->where('status', 'activo');
            })
            ->where(function ($query) use ($companyId) {
                $query->whereHas('role', function ($roleQuery) {
                    $roleQuery->where('scope', 'global');
                });

                if ($companyId !== null) {
                    $query->orWhere(function ($companyQuery) use ($companyId) {
                        $companyQuery
                            ->where('user_roles.company_id', $companyId)
                            ->whereHas('role', function ($roleQuery) {
                                $roleQuery->where('scope', 'company');
                            });
                    });
                }
            })
            ->exists();
    }

    /**
     * Determina si el usuario tiene un permiso activo.
     *
     * Super Admin tiene acceso a todos los permisos activos.
     * Los demás roles deben tener el permiso asignado.
     */
    public function hasPermission(
        User $user,
        string $permissionCode,
        ?int $companyId = null
    ): bool {
        if ($user->status !== 'activo') {
            return false;
        }

        if ($this->hasRole($user, 'super_admin', $companyId)) {
            return $this->permissionExistsAndIsActive(
                $permissionCode
            );
        }

        return $user->userRoles()
            ->where('user_roles.status', 'activo')
            ->where(function ($query) use ($companyId) {
                $query->whereHas('role', function ($roleQuery) {
                    $roleQuery
                        ->where('scope', 'global')
                        ->where('status', 'activo');
                });

                if ($companyId !== null) {
                    $query->orWhere(function ($companyQuery) use ($companyId) {
                        $companyQuery
                            ->where('user_roles.company_id', $companyId)
                            ->whereHas('role', function ($roleQuery) {
                                $roleQuery
                                    ->where('scope', 'company')
                                    ->where('status', 'activo');
                            });
                    });
                }
            })
            ->whereHas('role.permissions', function ($permissionQuery) use ($permissionCode) {
                $permissionQuery
                    ->where('permissions.code', $permissionCode)
                    ->where('permissions.status', 'activo');
            })
            ->exists();
    }

    /**
     * Autoriza una acción o lanza una excepción.
     */
    public function authorize(
        User $user,
        string $permissionCode,
        ?int $companyId = null
    ): void {
        if (! $this->hasPermission(
            $user,
            $permissionCode,
            $companyId
        )) {
            throw new RuntimeException(
                "El usuario no tiene permiso para realizar la acción [{$permissionCode}]."
            );
        }
    }

    /**
     * Obtiene los roles activos del usuario para una empresa.
     *
     * Los roles globales siempre pueden aparecer.
     */
    public function getActiveRoles(
        User $user,
        ?int $companyId = null
    ) {
        return $user->userRoles()
            ->where('user_roles.status', 'activo')
            ->whereHas('role', function ($query) {
                $query->where('status', 'activo');
            })
            ->where(function ($query) use ($companyId) {
                $query->whereHas('role', function ($roleQuery) {
                    $roleQuery->where('scope', 'global');
                });

                if ($companyId !== null) {
                    $query->orWhere(function ($companyQuery) use ($companyId) {
                        $companyQuery
                            ->where('user_roles.company_id', $companyId)
                            ->whereHas('role', function ($roleQuery) {
                                $roleQuery->where('scope', 'company');
                            });
                    });
                }
            })
            ->with('role')
            ->get();
    }

    /**
     * Obtiene los permisos activos disponibles para el usuario.
     */
    public function getActivePermissions(
        User $user,
        ?int $companyId = null
    ) {
        $roles = $this->getActiveRoles(
            $user,
            $companyId
        );

        $permissionIds = $roles
            ->flatMap(function ($userRole) {
                return $userRole->role->permissions
                    ->where('status', 'activo')
                    ->pluck('id');
            })
            ->unique()
            ->values();

        if ($permissionIds->isEmpty()) {
            return collect();
        }

        return Permission::query()
            ->whereIn('id', $permissionIds)
            ->where('status', 'activo')
            ->orderBy('code')
            ->get();
    }

    /**
     * Comprueba que un permiso exista y esté activo.
     */
    protected function permissionExistsAndIsActive(
        string $permissionCode
    ): bool {
        return Permission::query()
            ->where('code', $permissionCode)
            ->where('status', 'activo')
            ->exists();
    }
}