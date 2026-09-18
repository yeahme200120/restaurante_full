<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
            ],
            'company_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'branch_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        try {
            $result = $this->authService->login(
                $request,
                $validated['email'],
                $validated['password'],
                $validated['company_id'] ?? null,
                $validated['branch_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Autenticación realizada correctamente.',
                'data' => $result,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authService->logout($user);

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $company = $request->attributes->get('company');
        $branch = $request->attributes->get('branch');

        if (! $company || ! $branch) {
            return response()->json([
                'success' => false,
                'message' => 'No existe un contexto activo para la sesión.',
            ], 403);
        }

        $license = $company->licenses()
            ->where('status', 'activa')
            ->where(function ($query) {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'La empresa no tiene una licencia válida.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                ],
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'legal_name' => $company->legal_name,
                    'tax_id' => $company->tax_id,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'logo_path' => $company->logo_path,
                    'status' => $company->status,
                    'timezone' => $company->timezone,
                    'locale' => $company->locale,
                ],
                'branch' => [
                    'id' => $branch->id,
                    'company_id' => $branch->company_id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'address' => $branch->address,
                    'city' => $branch->city,
                    'state' => $branch->state,
                    'postal_code' => $branch->postal_code,
                    'phone' => $branch->phone,
                    'email' => $branch->email,
                    'status' => $branch->status,
                    'is_main' => $branch->is_main,
                ],
                'license' => [
                    'id' => $license->id,
                    'license_key' => $license->license_key,
                    'plan' => $license->plan,
                    'status' => $license->status,
                    'starts_at' => $license->starts_at,
                    'expires_at' => $license->expires_at,
                    'revoked_at' => $license->revoked_at,
                ],
            ],
        ]);
    }
}
