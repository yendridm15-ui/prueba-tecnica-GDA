<?php

use App\Http\Middleware\AuthenticateToken;
use App\Http\Middleware\LogApiRequests;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            LogApiRequests::class,
        ]);

        $middleware->alias([
            'auth.token' => AuthenticateToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return ApiResponse::error('Datos inválidos', $exception->errors(), 422);
            }

            $status = match (true) {
                $exception instanceof ModelNotFoundException,
                $exception instanceof NotFoundHttpException => 404,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };

            $message = match (true) {
                $exception instanceof ModelNotFoundException,
                $exception instanceof NotFoundHttpException => 'Recurso no encontrado',
                $exception instanceof MethodNotAllowedHttpException => 'Método no permitido',
                config('app.debug') => $exception->getMessage(),
                default => 'Error interno del servidor',
            };

            return ApiResponse::error($message, null, $status);
        });
    })->create();
