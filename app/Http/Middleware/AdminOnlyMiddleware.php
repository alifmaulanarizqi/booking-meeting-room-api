<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('auth_user_role') !== 'ADMIN') {
            throw new ApiException(
                'FORBIDDEN',
                'Akses ditolak. Endpoint ini khusus untuk role ADMIN.',
                403
            );
        }

        return $next($request);
    }
}