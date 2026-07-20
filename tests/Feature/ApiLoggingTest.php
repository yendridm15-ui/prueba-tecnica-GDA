<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;

test('guarda el log de entrada y salida con la ip del cliente', function () {
    Log::shouldReceive('channel')->with('api')->twice()->andReturnSelf();
    Log::shouldReceive('info')->once()->withArgs(
        fn (string $message, array $context): bool => $message === 'API request'
            && $context['ip'] === '127.0.0.1'
            && $context['method'] === 'POST'
    );
    Log::shouldReceive('info')->once()->withArgs(
        fn (string $message, array $context): bool => $message === 'API response'
            && $context['ip'] === '127.0.0.1'
            && $context['status'] === 422
    );

    $this->postJson('/api/login', []);
});

test('enmascara los campos sensibles antes de loguear', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $logged = [];
    Log::shouldReceive('channel')->with('api')->andReturnSelf();
    Log::shouldReceive('info')->twice()->withArgs(function (string $message, array $context) use (&$logged): bool {
        $logged[$message] = $context;

        return true;
    });

    $this->postJson('/api/login', ['email' => 'john@example.com', 'password' => 'password']);

    expect($logged['API request']['payload']['password'])->toBe('*****')
        ->and($logged['API response']['body']['data']['token'])->toBe('*****');
});

test('en producción solo guarda el log de entrada', function () {
    $this->app['env'] = 'production';

    Log::shouldReceive('channel')->with('api')->once()->andReturnSelf();
    Log::shouldReceive('info')->once()->withArgs(
        fn (string $message): bool => $message === 'API request'
    );

    $this->postJson('/api/login', []);
});

test('con los logs de salida apagados solo guarda el de entrada', function () {
    config(['api.log_responses' => false]);

    Log::shouldReceive('channel')->with('api')->once()->andReturnSelf();
    Log::shouldReceive('info')->once()->withArgs(
        fn (string $message): bool => $message === 'API request'
    );

    $this->postJson('/api/login', []);
});
