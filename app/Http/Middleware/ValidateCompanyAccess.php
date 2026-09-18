<?php

namespace App\Http\Middleware;

use App\Services\Company\CompanyContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidateCompanyAccess
{
    public function __construct(
        protected CompanyContextService $companyContextService
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        try {
            $context = $this->companyContextService->resolve(
                $user,
                $request->input('company_id'),
                $request->input('branch_id')
            );

            $request->attributes->set(
                'company_context',
                $context
            );

            $request->attributes->set(
                'company',
                $context['company']
            );

            $request->attributes->set(
                'branch',
                $context['branch']
            );
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 403);
        }

        return $next($request);
    }
}
