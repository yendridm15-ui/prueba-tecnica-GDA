<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Valida las credenciales y, si todo está bien, genera un token nuevo.
     * De paso limpia los tokens vencidos que tenga el usuario.
     */
    public function login(string $email, string $password): ?ApiToken
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        $user->apiTokens()->where('expires_at', '<', now())->delete();

        return $user->apiTokens()->create([
            'token' => $this->generateUniqueToken($email),
            'expires_at' => now()->addMinutes(config('api.token_ttl_minutes')),
        ]);
    }

    /**
     * El token es un sha1 de: email + fecha y hora del login + un random entre 200 y 500.
     * El ciclo revisa que no exista ya en la tabla antes de devolverlo, para que nunca se repita.
     */
    private function generateUniqueToken(string $email): string
    {
        do {
            $token = sha1($email.now()->format('Y-m-d H:i:s.u').random_int(200, 500));
        } while (ApiToken::query()->where('token', $token)->exists());

        return $token;
    }
}
