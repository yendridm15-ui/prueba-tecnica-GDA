<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    private const MASKED_FIELDS = ['password', 'token'];

    /**
     * Guarda el log de entrada de cada request con la IP de donde viene.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::channel('api')->info('API request', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'uri' => $request->fullUrl(),
            'payload' => $this->mask($request->all()),
        ]);

        return $next($request);
    }

    /**
     * Guarda el log de salida ya después de responder.
     * En producción, o con API_LOG_RESPONSES en false, este log no se guarda;
     * el de entrada queda siempre.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (app()->isProduction() || ! config('api.log_responses')) {
            return;
        }

        Log::channel('api')->info('API response', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'uri' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'body' => $response instanceof JsonResponse ? $this->mask($response->getData(true)) : null,
        ]);
    }

    /**
     * Tapa los campos sensibles antes de mandarlos al log, sin importar
     * qué tan anidados vengan.
     */
    private function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->mask($value);
            } elseif (in_array($key, self::MASKED_FIELDS, true)) {
                $data[$key] = '*****';
            }
        }

        return $data;
    }
}
