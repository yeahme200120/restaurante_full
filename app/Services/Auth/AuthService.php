<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Company\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

class AuthService
{
    public function __construct(
        protected CompanyContextService $companyContextService,
        protected AuditService $auditService
    ) {}

    public function login(
        Request $request,
        string $email,
        string $password,
        ?int $companyId = null,
        ?int $branchId = null
    ): array {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            $exception = new RuntimeException(
                'Las credenciales proporcionadas no son válidas.'
            );

            $this->auditFailure(
                action: 'auth.login_failed',
                exception: $exception,
                model: $user,
                module: 'auth',
                errorCode: 'INVALID_CREDENTIALS',
                request: $request,
                companyId: $user?->company_id,
                branchId: $user?->branch_id
            );

            throw $exception;
        }

        if (! $user->status || $user->status !== 'activo') {
            $exception = new RuntimeException(
                'El usuario no está activo.'
            );

            $this->auditFailure(
                action: 'auth.login_failed',
                exception: $exception,
                model: $user,
                module: 'auth',
                errorCode: 'USER_INACTIVE',
                request: $request,
                userId: $user->id,
                companyId: $user->company_id,
                branchId: $user->branch_id
            );

            throw $exception;
        }

        try {
            $context = $this->companyContextService->resolve(
                $user,
                $companyId,
                $branchId
            );

            $token = $user->createToken(
                'api',
                $this->resolveTokenAbilities()
            )->plainTextToken;

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
                'last_activity_at' => now(),
            ])->save();

            $freshUser = $user->fresh();

            $this->auditSuccess(
                action: 'auth.login',
                model: $freshUser,
                module: 'auth',
                request: $request,
                userId: $freshUser->id,
                companyId: $context['company']->id ?? $freshUser->company_id,
                branchId: $context['branch']->id ?? $freshUser->branch_id
            );

            return [
                'token' => $token,
                'user' => $freshUser,
                'company' => $context['company'],
                'branch' => $context['branch'],
            ];
        } catch (Throwable $exception) {
            $this->auditFailure(
                action: 'auth.login_failed',
                exception: $exception,
                model: $user,
                module: 'auth',
                errorCode: 'LOGIN_PROCESS_ERROR',
                request: $request,
                userId: $user->id,
                companyId: $user->company_id,
                branchId: $user->branch_id
            );

            throw $exception;
        }
    }

    public function logout(User $user): void
    {
        $request = request();

        $company = $request->attributes->get('company');
        $branch = $request->attributes->get('branch');

        $companyId = $company?->id;
        $branchId = $branch?->id;

        try {
            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
            }

            $this->auditSuccess(
                action: 'auth.logout',
                model: $user,
                module: 'auth',
                request: $request,
                userId: $user->id,
                companyId: $companyId,
                branchId: $branchId
            );
        } catch (Throwable $exception) {
            $this->auditFailure(
                action: 'auth.logout_failed',
                exception: $exception,
                model: $user,
                module: 'auth',
                errorCode: 'LOGOUT_ERROR',
                request: $request,
                userId: $user->id,
                companyId: $companyId,
                branchId: $branchId
            );

            throw $exception;
        }
    }

    protected function resolveTokenAbilities(): array
    {
        return [
            'api',
        ];
    }

    private function auditSuccess(
        string $action,
        ?User $model,
        string $module,
        Request $request,
        ?int $userId = null,
        ?int $companyId = null,
        ?int $branchId = null
    ): void {
        try {
            $this->auditService->success(
                action: $action,
                model: $model,
                module: $module,
                request: $request,
                userId: $userId,
                companyId: $companyId,
                branchId: $branchId
            );
        } catch (Throwable) {
        }
    }

    private function auditFailure(
        string $action,
        Throwable $exception,
        ?User $model,
        string $module,
        string $errorCode,
        Request $request,
        ?int $userId = null,
        ?int $companyId = null,
        ?int $branchId = null
    ): void {
        try {
            $this->auditService->failure(
                action: $action,
                exception: $exception,
                model: $model,
                module: $module,
                errorCode: $errorCode,
                request: $request,
                userId: $userId,
                companyId: $companyId,
                branchId: $branchId
            );
        } catch (Throwable) {
        }
    }
}
