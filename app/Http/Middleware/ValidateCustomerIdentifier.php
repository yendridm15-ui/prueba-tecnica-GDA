<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ValidateCustomerIdentifier
{
    /**
     * Exige que venga el dni o el email para poder buscar al customer.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validator = Validator::make($request->all(), [
            'dni' => ['required_without:email', 'nullable', 'string', 'max:45'],
            'email' => ['required_without:dni', 'nullable', 'string', 'email', 'max:120'],
        ], [
            'dni.required_without' => 'Debe indicar el dni o el email del customer.',
            'email.required_without' => 'Debe indicar el dni o el email del customer.',
            'string' => 'El campo :attribute debe ser texto.',
            'email' => 'El campo :attribute debe ser un correo válido.',
            'max.string' => 'El campo :attribute no debe superar :max caracteres.',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Parámetros de búsqueda inválidos', $validator->errors(), 422);
        }

        return $next($request);
    }
}
