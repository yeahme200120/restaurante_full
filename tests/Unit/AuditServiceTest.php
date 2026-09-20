<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_success_audit_log(): void
    {
        $service = app(AuditService::class);

        $audit = $service->success(
            action: 'product.created',
            module: 'products',
            device: 'test'
        );

        $this->assertInstanceOf(AuditLog::class, $audit);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $audit->id,
            'action' => 'product.created',
            'module' => 'products',
            'result' => 'success',
            'device' => 'test',
        ]);

        $this->assertNotNull($audit->event_id);
        $this->assertNotNull($audit->request_id);
    }

    public function test_it_stores_old_and_new_values(): void
    {
        $service = app(AuditService::class);

        $audit = $service->success(
            action: 'product.updated',
            module: 'products',
            oldValues: [
                'price' => 50,
                'active' => true,
            ],
            newValues: [
                'price' => 55,
                'active' => false,
            ],
            device: 'test'
        );

        $audit->refresh();

        $this->assertSame([
            'price' => 50,
            'active' => true,
        ], $audit->old_values);

        $this->assertSame([
            'price' => 55,
            'active' => false,
        ], $audit->new_values);
    }

    public function test_it_creates_an_error_audit_log(): void
    {
        $service = app(AuditService::class);

        $exception = new RuntimeException(
            'Error de prueba de auditoría'
        );

        $audit = $service->failure(
            action: 'product.update_failed',
            exception: $exception,
            module: 'products',
            errorCode: 'TEST_ERROR',
            device: 'test'
        );

        $this->assertInstanceOf(AuditLog::class, $audit);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $audit->id,
            'action' => 'product.update_failed',
            'module' => 'products',
            'result' => 'error',
            'error_code' => 'TEST_ERROR',
            'error_message' => 'Error de prueba de auditoría',
            'device' => 'test',
        ]);
    }

    public function test_it_preserves_idempotency_key(): void
    {
        $service = app(AuditService::class);

        $key = 'audit-test-idempotency-001';

        $audit = $service->success(
            action: 'sale.created',
            module: 'sales',
            idempotencyKey: $key,
            device: 'test'
        );

        $this->assertSame(
            $key,
            $audit->idempotency_key
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $audit->id,
            'idempotency_key' => $key,
        ]);
    }

    public function test_it_preserves_explicit_request_context(): void
    {
        $service = app(AuditService::class);

        $audit = $service->success(
            action: 'inventory.adjusted',
            module: 'inventory',
            userId: 25,
            roleId: 3,
            companyId: 7,
            branchId: 4,
            device: 'flutter'
        );

        $this->assertSame(25, $audit->user_id);
        $this->assertSame(3, $audit->role_id);
        $this->assertSame(7, $audit->company_id);
        $this->assertSame(4, $audit->branch_id);
        $this->assertSame('flutter', $audit->device);
    }

    public function test_it_generates_unique_event_and_request_ids(): void
    {
        $service = app(AuditService::class);

        $first = $service->success(
            action: 'test.first',
            module: 'audit',
            device: 'test'
        );

        $second = $service->success(
            action: 'test.second',
            module: 'audit',
            device: 'test'
        );

        $this->assertNotSame(
            $first->event_id,
            $second->event_id
        );

        $this->assertNotSame(
            $first->request_id,
            $second->request_id
        );
    }
}
