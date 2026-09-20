<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_audit_log_when_login_succeeds(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
            'password' => Hash::make('password'),
        ]);

        $company = Company::create([
            'name' => 'Empresa de Prueba',
            'legal_name' => 'Empresa de Prueba S.A. de C.V.',
            'tax_id' => 'TEST010101AAA',
            'email' => 'empresa@example.com',
            'phone' => '5555555555',
            'status' => 'activa',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Principal',
            'code' => 'TEST-001',
            'address' => 'Dirección de prueba',
            'city' => 'Cuautla',
            'state' => 'Morelos',
            'postal_code' => '62700',
            'phone' => '5555555555',
            'email' => 'sucursal@example.com',
            'status' => 'activa',
            'is_main' => true,
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-LICENSE-001',
            'plan' => 'premium',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
            'revoked_at' => null,
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $user->branches()->attach($branch->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $request = request()->create('/api/v1/login', 'POST', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $service = app(AuthService::class);

        $result = $service->login(
            $request,
            $user->email,
            'password'
        );

        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);

        $this->assertSame($user->id, $result['user']->id);
        $this->assertSame($company->id, $result['company']->id);
        $this->assertSame($branch->id, $result['branch']->id);

        $audit = AuditLog::query()
            ->where('action', 'auth.login')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('auth', $audit->module);
        $this->assertSame('success', $audit->result);
        $this->assertSame($user->id, $audit->user_id);
        $this->assertSame($company->id, $audit->company_id);
        $this->assertSame($branch->id, $audit->branch_id);
        $this->assertNotNull($audit->event_id);
        $this->assertNotNull($audit->request_id);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);

        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_activity_at);
    }

    public function test_it_creates_an_audit_log_when_login_credentials_are_invalid(): void
    {
        $request = request()->create('/api/v1/login', 'POST');

        $service = app(AuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Las credenciales proporcionadas no son válidas.'
        );

        try {
            $service->login(
                $request,
                'nonexistent@example.com',
                'wrong-password'
            );
        } catch (RuntimeException $exception) {
            $audit = AuditLog::query()
                ->where('action', 'auth.login_failed')
                ->latest('id')
                ->first();

            $this->assertNotNull($audit);
            $this->assertSame('auth', $audit->module);
            $this->assertSame('error', $audit->result);
            $this->assertSame(
                'INVALID_CREDENTIALS',
                $audit->error_code
            );
            $this->assertNull($audit->user_id);
            $this->assertNotNull($audit->request_id);

            throw $exception;
        }
    }

    public function test_it_creates_an_audit_log_when_user_is_inactive(): void
    {
        $user = User::factory()->create([
            'status' => 'inactivo',
            'password' => Hash::make('password'),
        ]);

        $request = request()->create('/api/v1/login', 'POST');

        $service = app(AuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'El usuario no está activo.'
        );

        try {
            $service->login(
                $request,
                $user->email,
                'password'
            );
        } catch (RuntimeException $exception) {
            $audit = AuditLog::query()
                ->where('action', 'auth.login_failed')
                ->latest('id')
                ->first();

            $this->assertNotNull($audit);
            $this->assertSame('auth', $audit->module);
            $this->assertSame('error', $audit->result);
            $this->assertSame(
                'USER_INACTIVE',
                $audit->error_code
            );
            $this->assertSame($user->id, $audit->user_id);
            $this->assertNotNull($audit->request_id);

            throw $exception;
        }
    }

    public function test_it_creates_an_audit_log_when_login_context_is_invalid(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
            'password' => Hash::make('password'),
        ]);

        $request = request()->create('/api/v1/login', 'POST');

        $service = app(AuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'No existe una empresa activa autorizada para el usuario.'
        );

        try {
            $service->login(
                $request,
                $user->email,
                'password'
            );
        } catch (RuntimeException $exception) {
            $audit = AuditLog::query()
                ->where('action', 'auth.login_failed')
                ->latest('id')
                ->first();

            $this->assertNotNull($audit);
            $this->assertSame('auth', $audit->module);
            $this->assertSame('error', $audit->result);
            $this->assertSame(
                'LOGIN_PROCESS_ERROR',
                $audit->error_code
            );
            $this->assertSame($user->id, $audit->user_id);
            $this->assertNotNull($audit->request_id);

            throw $exception;
        }
    }

    public function test_it_creates_an_audit_log_when_logout_succeeds(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $token = $user->createToken(
            'api',
            ['api']
        );

        $accessToken = $token->accessToken;

        $user->withAccessToken($accessToken);

        $service = app(AuthService::class);

        $service->logout($user);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $accessToken->id,
        ]);

        $audit = AuditLog::query()
            ->where('action', 'auth.logout')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('auth', $audit->module);
        $this->assertSame('success', $audit->result);
        $this->assertSame($user->id, $audit->user_id);
        $this->assertNotNull($audit->event_id);
        $this->assertNotNull($audit->request_id);
    }

    public function test_audit_logs_never_store_password_or_token(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
            'password' => Hash::make('password'),
        ]);

        $company = Company::create([
            'name' => 'Empresa Seguridad',
            'legal_name' => 'Empresa Seguridad S.A. de C.V.',
            'tax_id' => 'TEST020202BBB',
            'email' => 'security@example.com',
            'status' => 'activa',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Seguridad',
            'code' => 'SEC-001',
            'status' => 'activa',
            'is_main' => true,
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'SECURITY-LICENSE-001',
            'plan' => 'premium',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $user->branches()->attach($branch->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $request = request()->create('/api/v1/login', 'POST');

        $service = app(AuthService::class);

        $result = $service->login(
            $request,
            $user->email,
            'password'
        );

        $token = $result['token'];

        $audit = AuditLog::query()
            ->where('action', 'auth.login')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);

        $auditData = json_encode($audit->toArray());

        $this->assertStringNotContainsString(
            'password',
            strtolower($auditData)
        );

        $this->assertStringNotContainsString(
            $token,
            $auditData
        );
    }
}
