<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createValidContext(): array
    {
        $user = User::factory()->create([
            'status' => 'activo',
        ]);

        $company = Company::create([
            'name' => 'Restaurante Feature Test',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $company->id,
            'license_key' => 'FEATURE-TEST-'.$company->id,
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Feature Test',
            'code' => 'FEATURE',
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

        return [
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
        ];
    }

    public function test_authenticated_user_can_access_me(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $response = $this->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $response->assertJsonPath(
            'data.user.id',
            $context['user']->id
        );
    }

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_inactive_user_loses_access_after_login(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $context['user']->update([
            'status' => 'suspendido',
        ]);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertForbidden();

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_suspended_company_loses_access_after_login(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $context['company']->update([
            'status' => 'suspendida',
        ]);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertForbidden();

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_expired_license_loses_access_after_login(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $license = $context['company']
            ->licenses()
            ->latest('id')
            ->first();

        $license->update([
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertForbidden();

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_revoked_license_loses_access_after_login(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $license = $context['company']
            ->licenses()
            ->latest('id')
            ->first();

        $license->update([
            'revoked_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertForbidden();

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_inactive_company_assignment_loses_access_after_login(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $context['user']->companies()->updateExistingPivot(
            $context['company']->id,
            [
                'status' => 'inactiva',
            ]
        );

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertForbidden();

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_inactive_branch_assignment_loses_access_after_login(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $context['user']->branches()->updateExistingPivot(
            $context['branch']->id,
            [
                'status' => 'inactiva',
            ]
        );

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertForbidden();

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_inactive_branch_loses_access_after_login(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $context['branch']->update([
            'status' => 'inactiva',
        ]);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertForbidden();

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $email = 'rate-limit@restaurante.test';

        User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => 'activo',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->postJson(
                '/api/v1/auth/login',
                [
                    'email' => $email,
                    'password' => 'password-incorrecto',
                ]
            );

            $response->assertUnauthorized();
        }

        $response = $this->postJson(
            '/api/v1/auth/login',
            [
                'email' => $email,
                'password' => 'password-incorrecto',
            ]
        );

        $response->assertStatus(429);
    }

    public function test_me_returns_authenticated_context(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $response = $this->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath(
                'data.user.id',
                $context['user']->id
            )
            ->assertJsonPath(
                'data.user.email',
                $context['user']->email
            )
            ->assertJsonPath(
                'data.company.id',
                $context['company']->id
            )
            ->assertJsonPath(
                'data.company.name',
                $context['company']->name
            )
            ->assertJsonPath(
                'data.branch.id',
                $context['branch']->id
            )
            ->assertJsonPath(
                'data.branch.company_id',
                $context['company']->id
            )
            ->assertJsonPath(
                'data.license.id',
                $context['company']
                    ->licenses()
                    ->latest('id')
                    ->first()
                    ->id
            )
            ->assertJsonPath(
                'data.license.status',
                'activa'
            )
            ->assertJsonMissingPath(
                'data.user.password'
            );
    }

    public function test_me_accepts_authorized_company_and_branch_context(): void
    {
        $context = $this->createValidContext();

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $response = $this->getJson(
            '/api/v1/auth/me?company_id='
            .$context['company']->id
            .'&branch_id='
            .$context['branch']->id
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.company.id',
                $context['company']->id
            )
            ->assertJsonPath(
                'data.branch.id',
                $context['branch']->id
            );
    }

    public function test_me_rejects_unauthorized_company_context(): void
    {
        $context = $this->createValidContext();

        $otherUser = User::factory()->create([
            'status' => 'activo',
        ]);

        $otherCompany = Company::create([
            'name' => 'Otra Empresa',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $otherCompany->id,
            'license_key' => 'OTHER-CONTEXT-'.$otherCompany->id,
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $response = $this->getJson(
            '/api/v1/auth/me?company_id='
            .$otherCompany->id
            .'&branch_id='
            .$context['branch']->id
        );

        $response->assertForbidden();

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_me_rejects_branch_from_another_company(): void
    {
        $context = $this->createValidContext();

        $otherCompany = Company::create([
            'name' => 'Empresa Sucursal Externa',
            'status' => 'activa',
        ]);

        CompanyLicense::create([
            'company_id' => $otherCompany->id,
            'license_key' => 'OTHER-BRANCH-'.$otherCompany->id,
            'plan' => 'standard',
            'status' => 'activa',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'name' => 'Sucursal Externa',
            'code' => 'EXTERNA',
            'status' => 'activa',
            'is_main' => true,
        ]);

        Sanctum::actingAs(
            $context['user'],
            ['api']
        );

        $response = $this->getJson(
            '/api/v1/auth/me?company_id='
            .$context['company']->id
            .'&branch_id='
            .$otherBranch->id
        );

        $response->assertForbidden();

        $response->assertJson([
            'success' => false,
        ]);
    }
}
