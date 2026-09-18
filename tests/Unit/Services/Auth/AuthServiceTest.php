<?php

namespace Tests\Unit\Services\Auth;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials_and_context(): void
    {
        $user = User::factory()->create([
            'email' => 'usuario@restaurante.test',
            'password' => Hash::make('password123'),
            'status' => 'activo',
        ]);

        $company = Company::create([
            'name' => 'Restaurante de prueba',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'AUTH-TEST-001',
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal principal',
            'code' => 'MAIN',
            'status' => 'activa',
            'is_main' => true,
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $user->branches()->attach($branch->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $request = Request::create(
            '/api/v1/auth/login',
            'POST'
        );

        $request->server->set(
            'REMOTE_ADDR',
            '127.0.0.1'
        );

        $service = app(AuthService::class);

        $result = $service->login(
            $request,
            'usuario@restaurante.test',
            'password123'
        );

        $this->assertNotEmpty($result['token']);
        $this->assertSame($user->id, $result['user']->id);
        $this->assertSame($company->id, $result['company']->id);
        $this->assertSame($branch->id, $result['branch']->id);

        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_activity_at);
        $this->assertSame('127.0.0.1', $user->last_login_ip);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'usuario@restaurante.test',
            'password' => Hash::make('password123'),
            'status' => 'activo',
        ]);

        $request = Request::create(
            '/api/v1/auth/login',
            'POST'
        );

        $service = app(AuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Las credenciales proporcionadas no son válidas.'
        );

        $service->login(
            $request,
            'usuario@restaurante.test',
            'password-incorrecto'
        );
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@restaurante.test',
            'password' => Hash::make('password123'),
            'status' => 'suspendido',
        ]);

        $request = Request::create(
            '/api/v1/auth/login',
            'POST'
        );

        $service = app(AuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'El usuario no está activo.'
        );

        $service->login(
            $request,
            'inactivo@restaurante.test',
            'password123'
        );
    }

    public function test_company_without_valid_license_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'sinlicencia@restaurante.test',
            'password' => Hash::make('password123'),
            'status' => 'activo',
        ]);

        $company = Company::create([
            'name' => 'Empresa sin licencia válida',
            'status' => 'activa',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal principal',
            'code' => 'MAIN',
            'status' => 'activa',
            'is_main' => true,
        ]);

        $user->companies()->attach($company->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $user->branches()->attach($branch->id, [
            'is_default' => true,
            'status' => 'activa',
        ]);

        $request = Request::create(
            '/api/v1/auth/login',
            'POST'
        );

        $service = app(AuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'La empresa no tiene una licencia válida.'
        );

        $service->login(
            $request,
            'sinlicencia@restaurante.test',
            'password123'
        );
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $token = $user->createToken(
            'api',
            ['api']
        );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );

        $user->withAccessToken(
            $token->accessToken
        );

        $service = app(AuthService::class);

        $service->logout($user);

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }
}
