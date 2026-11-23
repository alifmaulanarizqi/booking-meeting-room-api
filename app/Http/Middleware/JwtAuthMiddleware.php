<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            throw new ApiException(
                'UNAUTHORIZED',
                'Header Authorization Bearer token wajib disertakan.',
                401
            );
        }

        $payload = $this->authService->decodeToken($token);

        $request->attributes->set('auth_user_id', (int) $payload->sub);
        $request->attributes->set('auth_user_role', (string) $payload->role);

        return $next($request);
    }
}