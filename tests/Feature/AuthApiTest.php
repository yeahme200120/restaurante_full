<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedContext(): array
    {
        $user = User::factory()->create([
            'status' => 'activo',
            'password' => Hash::make('password123'),
        ]);

        $company = Company::create([
            'name' => 'Empresa de Prueba',
            'legal_name' => 'Empresa de Prueba S.A. de C.V.',
            'tax_id' => 'TEST010101AAA',
            'email' => 'empresa@test.local',
            'phone' => '7350000000',
            'status' => 'activa',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Principal',
            'code' => 'SUC-001',
            'address' => 'Dirección de prueba',
            'city' => 'Cuautla',
            'state' => 'Morelos',
            'postal_code' => '62700',
            'phone' => '7350000001',
            'email' => 'sucursal@test.local',
            'status' => 'activa',
            'is_main' => true,
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-LICENSE-001',
            'plan' => 'pro',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
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

        return [
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
        ];
    }

    public function test_login_endpoint_returns_token_and_creates_audit(): void
    {
        $context = $this->createAuthenticatedContext();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $context['user']->email,
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user',
                    'company',
                    'branch',
                ],
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $context['user']->id,
            'name' => 'api',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'module' => 'auth',
            'result' => 'success',
            'user_id' => $context['user']->id,
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
        ]);
    }

    public function test_login_endpoint_rejects_invalid_credentials(): void
    {
        $context = $this->createAuthenticatedContext();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $context['user']->email,
            'password' => 'password-incorrecta',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Las credenciales proporcionadas no son válidas.',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_failed',
            'module' => 'auth',
            'result' => 'error',
            'error_code' => 'INVALID_CREDENTIALS',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_endpoint_returns_authenticated_context(): void
    {
        $context = $this->createAuthenticatedContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $response = $this->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $context['user']->id,
                        'email' => $context['user']->email,
                    ],
                    'company' => [
                        'id' => $context['company']->id,
                        'name' => $context['company']->name,
                    ],
                    'branch' => [
                        'id' => $context['branch']->id,
                        'name' => $context['branch']->name,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                    'company',
                    'branch',
                    'license',
                ],
            ]);
    }

    public function test_me_endpoint_rejects_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_me_endpoint_rejects_company_without_valid_license(): void
    {
        $context = $this->createAuthenticatedContext();

        CompanyLicense::query()
            ->where('company_id', $context['company']->id)
            ->update([
                'expires_at' => now()->subDay(),
            ]);

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $response = $this->getJson('/api/v1/auth/me');

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'La empresa no tiene una licencia válida.',
            ]);
    }

    public function test_logout_endpoint_revokes_token_and_creates_audit(): void
    {
        $context = $this->createAuthenticatedContext();

        $token = $context['user']->createToken(
            'api',
            ['api']
        );

        $accessToken = $token->accessToken;

        $context['user']->withAccessToken($accessToken);

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Sesión cerrada correctamente.',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $accessToken->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.logout',
            'module' => 'auth',
            'result' => 'success',
            'user_id' => $context['user']->id,
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
        ]);
    }

    public function test_invalid_token_cannot_access_me(): void
    {
        $response = $this->withToken(
            'token-invalido'
        )->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }
}
