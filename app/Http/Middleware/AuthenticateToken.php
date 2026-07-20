<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    /**
     * Revisa el token que llega en el header y corta el acceso
     * si no viene, no existe o ya está vencido.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-Api-Token');

        if ($token === null || $token === '') {
            return ApiResponse::error('Token no proporcionado', null, 401);
        }

        $apiToken = ApiToken::query()->where('token', $token)->first();

        if ($apiToken === null) {
            return ApiResponse::error('Token inválido', null, 401);
        }

        if ($apiToken->isExpired()) {
            return ApiResponse::error('Token vencido', null, 401);
        }

        $request->setUserResolver(fn () => $apiToken->user);

        return $next($request);
    }
}
