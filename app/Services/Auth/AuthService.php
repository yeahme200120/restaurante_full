<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Company\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthService
{
    public function __construct(
        protected CompanyContextService $companyContextService
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
            throw new RuntimeException(
                'Las credenciales proporcionadas no son válidas.'
            );
        }

        if (! $user->status || $user->status !== 'activo') {
            throw new RuntimeException(
                'El usuario no está activo.'
            );
        }

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

        return [
            'token' => $token,
            'user' => $user->fresh(),
            'company' => $context['company'],
            'branch' => $context['branch'],
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }
    }

    protected function resolveTokenAbilities(): array
    {
        return [
            'api',
        ];
    }
}
