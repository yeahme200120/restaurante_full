<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class AuditService
{
    public function record(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $module = null,
        ?string $result = 'success',
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?string $eventId = null,
        ?string $idempotencyKey = null,
        ?Request $request = null,
        ?int $userId = null,
        ?int $roleId = null,
        ?int $companyId = null,
        ?int $branchId = null,
        ?string $device = null
    ): AuditLog {
        $request ??= request();

        $user = Auth::user();

        $userId ??= $user?->id;
        $companyId ??= $user?->company_id;

        if ($branchId === null && $user !== null) {
            $branchId = $user->branch_id ?? null;
        }

        if ($roleId === null && $user !== null) {
            $roleId = $this->resolveRoleId($user);
        }

        $recordType = null;
        $recordId = null;

        if ($model !== null) {
            $recordType = $model->getMorphClass();
            $recordId = $model->getKey();
        }

        $requestId = $request?->header('X-Request-ID');

        if ($requestId === null || $requestId === '') {
            $requestId = (string) Str::uuid();
        }

        if ($eventId === null && $result === 'success') {
            $eventId = (string) Str::uuid();
        }

        return AuditLog::create([
            'user_id' => $userId,
            'role_id' => $roleId,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'module' => $module,
            'action' => $action,
            'record_type' => $recordType,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'result' => $result ?? 'success',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'event_id' => $eventId,
            'idempotency_key' => $idempotencyKey,
            'request_id' => $requestId,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'device' => $device ?? $this->resolveDevice($request),
            'created_at' => now(),
        ]);
    }

    public function success(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $module = null,
        ?string $eventId = null,
        ?string $idempotencyKey = null,
        ?Request $request = null,
        ?int $userId = null,
        ?int $roleId = null,
        ?int $companyId = null,
        ?int $branchId = null,
        ?string $device = null
    ): AuditLog {
        return $this->record(
            action: $action,
            model: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            module: $module,
            result: 'success',
            eventId: $eventId,
            idempotencyKey: $idempotencyKey,
            request: $request,
            userId: $userId,
            roleId: $roleId,
            companyId: $companyId,
            branchId: $branchId,
            device: $device
        );
    }

    public function failure(
        string $action,
        ?Throwable $exception = null,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $module = null,
        ?string $errorCode = null,
        ?string $eventId = null,
        ?string $idempotencyKey = null,
        ?Request $request = null,
        ?int $userId = null,
        ?int $roleId = null,
        ?int $companyId = null,
        ?int $branchId = null,
        ?string $device = null
    ): AuditLog {
        $errorMessage = $exception?->getMessage();

        return $this->record(
            action: $action,
            model: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            module: $module,
            result: 'error',
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            eventId: $eventId,
            idempotencyKey: $idempotencyKey,
            request: $request,
            userId: $userId,
            roleId: $roleId,
            companyId: $companyId,
            branchId: $branchId,
            device: $device
        );
    }

    private function resolveRoleId($user): ?int
    {
        try {
            if (method_exists($user, 'roles')) {
                $role = $user->roles()->first();

                return $role?->id;
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function resolveDevice(?Request $request): ?string
    {
        if ($request === null) {
            return null;
        }

        $device = $request->header('X-Device');

        if ($device !== null && $device !== '') {
            return Str::limit($device, 150, '');
        }

        $userAgent = $request->userAgent();

        if ($userAgent === null) {
            return null;
        }

        $userAgent = strtolower($userAgent);

        if (
            str_contains($userAgent, 'android') ||
            str_contains($userAgent, 'iphone') ||
            str_contains($userAgent, 'ipad')
        ) {
            return 'mobile';
        }

        if (
            str_contains($userAgent, 'windows') ||
            str_contains($userAgent, 'macintosh') ||
            str_contains($userAgent, 'linux')
        ) {
            return 'web';
        }

        return 'api';
    }
}
