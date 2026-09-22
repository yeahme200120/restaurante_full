<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->attributes->get('company');

        if (! $company) {
            return response()->json([
                'message' => 'No se pudo determinar la empresa activa.',
            ], 403);
        }

        $companyId = $company->id;

        $users = User::query()
            ->whereExists(function ($query) use ($companyId) {
                $query
                    ->selectRaw('1')
                    ->from('user_company_assignments')
                    ->whereColumn(
                        'user_company_assignments.user_id',
                        'users.id'
                    )
                    ->where('user_company_assignments.company_id', $companyId)
                    ->where('user_company_assignments.status', 'activa');
            })
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.status',
                'users.email_verified_at',
                'users.last_login_at',
                'users.last_login_ip',
                'users.last_activity_at',
                'users.created_at',
                'users.updated_at',
            ])
            ->orderBy('users.id')
            ->paginate(
                min((int) $request->input('per_page', 15), 100)
            );

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function store(
        StoreUserRequest $request,
        AuditService $auditService
    ): JsonResponse {
        $company = $request->attributes->get('company');

        if (! $company) {
            return response()->json([
                'message' => 'No se pudo determinar la empresa activa.',
            ], 403);
        }

        $companyId = (int) $company->id;

        $validated = $request->validated();

        try {
            $user = DB::transaction(function () use (
                $validated,
                $companyId,
                $auditService
            ) {
                $branch = Branch::query()
                    ->whereKey($validated['branch_id'])
                    ->where('company_id', $companyId)
                    ->where('status', 'activa')
                    ->first();

                if (! $branch) {
                    throw new \RuntimeException(
                        'La sucursal seleccionada no pertenece a la empresa activa o no está activa.'
                    );
                }

                $role = Role::query()
                    ->whereKey($validated['role_id'])
                    ->where('status', 'activo')
                    ->first();

                if (! $role) {
                    throw new \RuntimeException(
                        'El rol seleccionado no existe o no está activo.'
                    );
                }

                if ($role->scope !== 'company') {
                    throw new \RuntimeException(
                        'El rol seleccionado no puede asignarse a un usuario de empresa.'
                    );
                }

                if ($role->code === 'super_admin') {
                    throw new \RuntimeException(
                        'No se puede asignar el rol Super Admin mediante este endpoint.'
                    );
                }

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => $validated['password'],
                    'status' => $validated['status'] ?? 'activo',
                ]);

                DB::table('user_company_assignments')->insert([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'is_default' => true,
                    'status' => 'activa',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('user_branch_assignments')->insert([
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                    'is_default' => true,
                    'status' => 'activa',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                UserRole::create([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'company_id' => $companyId,
                    'status' => 'activo',
                    'assigned_at' => now(),
                ]);

                $auditService->record(
                    action: 'created',
                    model: $user,
                    newValues: [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'status' => $user->status,
                        'company_id' => $companyId,
                        'branch_id' => $branch->id,
                        'role_id' => $role->id,
                    ],
                    module: 'usuarios',
                    result: 'success',
                    companyId: $companyId,
                    branchId: $branch->id,
                );

                return $user;
            });

            return response()->json([
                'message' => 'Usuario creado correctamente.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                ],
            ], 201);
        } catch (Throwable $e) {
            try {
                $auditService->record(
                    action: 'user.create_failed',
                    module: 'usuarios',
                    result: 'error',
                    errorCode: 'USER_CREATE_FAILED',
                    errorMessage: $e->getMessage(),
                    companyId: $companyId,
                );
            } catch (Throwable) {
                // La auditoría de error no debe ocultar el error original.
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        AuditService $auditService
    ): JsonResponse {
        $company = $request->attributes->get('company');

        if (! $company) {
            return response()->json([
                'message' => 'No se pudo determinar la empresa activa.',
            ], 403);
        }

        $companyId = (int) $company->id;
        $validated = $request->validated();

        try {
            $updatedUser = DB::transaction(function () use (
                $validated,
                $user,
                $companyId,
                $auditService
            ) {
                $hasCompanyAssignment = DB::table('user_company_assignments')
                    ->where('user_id', $user->id)
                    ->where('company_id', $companyId)
                    ->where('status', 'activa')
                    ->exists();

                if (! $hasCompanyAssignment) {
                    throw new \RuntimeException(
                        'El usuario no pertenece a la empresa activa.'
                    );
                }

                $isTargetSuperAdmin = $user->userRoles()
                    ->where('user_roles.status', 'activo')
                    ->whereHas('role', function ($query) {
                        $query
                            ->where('code', 'super_admin')
                            ->where('scope', 'global')
                            ->where('status', 'activo');
                    })
                    ->exists();

                if ($isTargetSuperAdmin && $validated !== []) {
                    throw new \RuntimeException(
                        'No se puede modificar un usuario Super Admin mediante este endpoint.'
                    );
                }

                $oldValues = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                ];

                $userFields = [];

                foreach (['name', 'email', 'phone', 'status', 'password'] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $userFields[$field] = $validated[$field];
                    }
                }

                if ($userFields !== []) {
                    $user->update($userFields);
                }

                $branchId = null;
                $roleId = null;

                if (array_key_exists('branch_id', $validated)) {
                    $branch = Branch::query()
                        ->whereKey($validated['branch_id'])
                        ->where('company_id', $companyId)
                        ->where('status', 'activa')
                        ->first();

                    if (! $branch) {
                        throw new \RuntimeException(
                            'La sucursal seleccionada no pertenece a la empresa activa o no está activa.'
                        );
                    }

                    $existingBranchAssignment = DB::table('user_branch_assignments')
                        ->where('user_id', $user->id)
                        ->where('branch_id', $branch->id)
                        ->first();

                    if ($existingBranchAssignment) {
                        DB::table('user_branch_assignments')
                            ->where('id', $existingBranchAssignment->id)
                            ->update([
                                'status' => 'activa',
                                'is_default' => true,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('user_branch_assignments')->insert([
                            'user_id' => $user->id,
                            'branch_id' => $branch->id,
                            'is_default' => true,
                            'status' => 'activa',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('user_branch_assignments')
                        ->where('user_id', $user->id)
                        ->where('branch_id', '!=', $branch->id)
                        ->update([
                            'is_default' => false,
                            'updated_at' => now(),
                        ]);

                    $branchId = $branch->id;
                }

                if (array_key_exists('role_id', $validated)) {
                    $role = Role::query()
                        ->whereKey($validated['role_id'])
                        ->where('status', 'activo')
                        ->first();

                    if (! $role) {
                        throw new \RuntimeException(
                            'El rol seleccionado no existe o no está activo.'
                        );
                    }

                    if ($role->scope !== 'company' || $role->code === 'super_admin') {
                        throw new \RuntimeException(
                            'El rol seleccionado no puede asignarse a un usuario de empresa.'
                        );
                    }

                    $existingRoleAssignment = UserRole::query()
                        ->where('user_id', $user->id)
                        ->where('company_id', $companyId)
                        ->first();

                    if ($existingRoleAssignment) {
                        $existingRoleAssignment->update([
                            'role_id' => $role->id,
                            'status' => 'activo',
                            'assigned_at' => now(),
                        ]);
                    } else {
                        UserRole::create([
                            'user_id' => $user->id,
                            'role_id' => $role->id,
                            'company_id' => $companyId,
                            'status' => 'activo',
                            'assigned_at' => now(),
                        ]);
                    }

                    $roleId = $role->id;
                }

                $newValues = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'company_id' => $companyId,
                ];

                if ($branchId !== null) {
                    $newValues['branch_id'] = $branchId;
                }

                if ($roleId !== null) {
                    $newValues['role_id'] = $roleId;
                }

                $auditService->record(
                    action: 'updated',
                    model: $user,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    module: 'usuarios',
                    result: 'success',
                    companyId: $companyId,
                    branchId: $branchId,
                );

                return $user->fresh();
            });

            return response()->json([
                'message' => 'Usuario actualizado correctamente.',
                'data' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'phone' => $updatedUser->phone,
                    'status' => $updatedUser->status,
                ],
            ]);
        } catch (Throwable $e) {
            try {
                $auditService->record(
                    action: 'user.update_failed',
                    model: $user,
                    module: 'usuarios',
                    result: 'error',
                    errorCode: 'USER_UPDATE_FAILED',
                    errorMessage: $e->getMessage(),
                    companyId: $companyId,
                );
            } catch (Throwable) {
                // La auditoría de error no debe ocultar el error original.
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(
        User $user,
        AuditService $auditService
    ): JsonResponse {
        Gate::authorize('delete', $user);

        $company = request()->attributes->get('company');

        if (! $company) {
            return response()->json([
                'message' => 'No se pudo determinar la empresa activa.',
            ], 403);
        }

        $companyId = (int) $company->id;

        try {
            DB::transaction(function () use (
                $user,
                $auditService,
                $companyId
            ) {
                $oldValues = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                ];

                DB::table('user_company_assignments')
                    ->where('user_id', $user->id)
                    ->delete();

                DB::table('user_branch_assignments')
                    ->where('user_id', $user->id)
                    ->delete();

                DB::table('user_roles')
                    ->where('user_id', $user->id)
                    ->delete();

                $user->delete();

                $auditService->record(
                    action: 'deleted',
                    model: $user,
                    oldValues: $oldValues,
                    module: 'usuarios',
                    result: 'success',
                    companyId: $companyId,
                );
            });

            return response()->json([
                'message' => 'Usuario eliminado correctamente.',
            ]);
        } catch (Throwable $e) {
            try {
                $auditService->record(
                    action: 'user.delete_failed',
                    model: $user,
                    module: 'usuarios',
                    result: 'error',
                    errorCode: 'USER_DELETE_FAILED',
                    errorMessage: $e->getMessage(),
                    companyId: $companyId,
                );
            } catch (Throwable) {
                // La auditoría de error no debe ocultar el error original.
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}