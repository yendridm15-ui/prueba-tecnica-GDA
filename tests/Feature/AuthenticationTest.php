<?php

use App\Models\ApiToken;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

test('el login devuelve el token con el formato de respuesta', function () {
    $user = User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
        ])
        ->assertJsonStructure(['success', 'message', 'data' => ['token', 'expires_at']]);

    $token = $response->json('data.token');

    expect($token)->toMatch('/^[0-9a-f]{40}$/');
    expect(ApiToken::query()->where('token', $token)->whereBelongsTo($user)->exists())->toBeTrue();
});

test('el login falla con credenciales incorrectas', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $this->postJson('/api/login', [
        'email' => 'john@example.com',
        'password' => 'clave-mala',
    ])->assertUnauthorized()
        ->assertJson(['success' => false, 'message' => 'Credenciales inválidas', 'data' => null]);
});

test('la validación del login corre en el middleware', function (array $payload) {
    $this->postJson('/api/login', $payload)
        ->assertUnprocessable()
        ->assertJson(['success' => false, 'message' => 'Datos de inicio de sesión inválidos']);
})->with([
    'sin datos' => [[]],
    'email inválido' => [['email' => 'esto-no-es-un-correo', 'password' => 'secret']],
    'sin password' => [['email' => 'john@example.com']],
]);

test('los tokens no se repiten entre logins', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $credentials = ['email' => 'john@example.com', 'password' => 'password'];

    $firstToken = $this->postJson('/api/login', $credentials)->json('data.token');
    $secondToken = $this->postJson('/api/login', $credentials)->json('data.token');

    expect($firstToken)->not->toBe($secondToken);
});

describe('rutas protegidas', function () {
    beforeEach(function () {
        Route::middleware('auth.token')->get('/api/protected', fn () => ApiResponse::success('ok'));
    });

    test('deja pasar con un token válido', function () {
        $apiToken = ApiToken::factory()->create();

        $this->withToken($apiToken->token)
            ->getJson('/api/protected')
            ->assertSuccessful()
            ->assertJson(['success' => true, 'message' => 'ok']);
    });

    test('rechaza el request si no viene token', function () {
        $this->getJson('/api/protected')
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Token no proporcionado']);
    });

    test('rechaza un token que no existe', function () {
        $this->withToken(sha1('token-inventado'))
            ->getJson('/api/protected')
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Token inválido']);
    });

    test('rechaza un token vencido', function () {
        $apiToken = ApiToken::factory()->expired()->create();

        $this->withToken($apiToken->token)
            ->getJson('/api/protected')
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Token vencido']);
    });
});
