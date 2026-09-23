<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DisableCompanyModuleRequest;
use App\Http\Requests\Api\V1\EnableCompanyModuleRequest;
use App\Http\Resources\Api\V1\CompanyModuleResource;
use App\Http\Resources\Api\V1\ModuleResource;
use App\Models\Module;
use App\Services\Module\CompanyModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ModuleController extends Controller
{
    public function index(): JsonResponse
    {
        $modules = Module::query()
            ->where('status', 'activo')
            ->orderBy('sort_order')
            ->get([
                'id',
                'code',
                'name',
                'description',
                'status',
                'is_core',
                'sort_order',
                'metadata',
            ]);

        return response()->json([
            'data' => ModuleResource::collection($modules),
        ]);
    }

    public function companyModules(
        Request $request,
        CompanyModuleService $companyModuleService
    ): JsonResponse {
        $company = $request->attributes->get('company');

        if (! $company) {
            return response()->json([
                'message' => 'No se pudo determinar la empresa activa.',
            ], 403);
        }

        $modules = $companyModuleService->getEnabledModules($company);

        return response()->json([
            'data' => ModuleResource::collection($modules),
        ]);
    }

    public function enable(
        EnableCompanyModuleRequest $request,
        Module $module,
        CompanyModuleService $companyModuleService
    ): JsonResponse {
        $company = $request->attributes->get('company');

        if (! $company) {
            return response()->json([
                'message' => 'No se pudo determinar la empresa activa.',
            ], 403);
        }

        $user = Auth::user();

        if (
            ! $user
            || ! app(\App\Services\Authorization\AuthorizationService::class)
                ->hasRole($user, 'super_admin')
        ) {
            return response()->json([
                'message' => 'Solo el Super Admin puede habilitar módulos.',
            ], 403);
        }

        try {
            $companyModule = $companyModuleService->enable(
                $company,
                $module,
                $user->id
            );

            return response()->json([
                'message' => 'Módulo habilitado correctamente.',
                'data' => new CompanyModuleResource($companyModule),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function disable(
        DisableCompanyModuleRequest $request,
        Module $module,
        CompanyModuleService $companyModuleService
    ): JsonResponse {
        $company = $request->attributes->get('company');

        if (! $company) {
            return response()->json([
                'message' => 'No se pudo determinar la empresa activa.',
            ], 403);
        }

        $user = Auth::user();

        if (
            ! $user
            || ! app(\App\Services\Authorization\AuthorizationService::class)
                ->hasRole($user, 'super_admin')
        ) {
            return response()->json([
                'message' => 'Solo el Super Admin puede deshabilitar módulos.',
            ], 403);
        }

        try {
            $companyModule = $companyModuleService->disable(
                $company,
                $module,
                $user->id
            );

            return response()->json([
                'message' => 'Módulo deshabilitado correctamente.',
                'data' => new CompanyModuleResource($companyModule),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}