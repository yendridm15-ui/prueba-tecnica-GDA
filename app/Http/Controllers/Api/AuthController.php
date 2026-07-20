<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function login(Request $request): JsonResponse
    {
        $apiToken = $this->authService->login(
            $request->string('email')->value(),
            $request->string('password')->value(),
        );

        if ($apiToken === null) {
            return ApiResponse::error('Credenciales inválidas', null, 401);
        }

        return ApiResponse::success('Inicio de sesión exitoso', [
            'token' => $apiToken->token,
            'expires_at' => $apiToken->expires_at->toDateTimeString(),
        ]);
    }
}
