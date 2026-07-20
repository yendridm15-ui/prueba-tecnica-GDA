<?php

namespace App\Http\Middleware;

use App\Enums\Status;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ValidateCustomerRegistration
{
    /**
     * Valida todo lo del registro: obligatorios, formatos, duplicados,
     * y que la comuna exista, esté activa y sí pertenezca a la región que mandaron.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validator = Validator::make($request->all(), [
            'dni' => ['required', 'string', 'max:45', Rule::unique('customers', 'dni')],
            'email' => ['required', 'string', 'email', 'max:120', Rule::unique('customers', 'email')],
            'name' => ['required', 'string', 'max:45'],
            'last_name' => ['required', 'string', 'max:45'],
            'address' => ['nullable', 'string', 'max:255'],
            'id_reg' => [
                'required',
                'integer',
                Rule::exists('regions', 'id_reg')->where('status', Status::Active->value),
            ],
            'id_com' => [
                'required',
                'integer',
                Rule::exists('communes', 'id_com')
                    ->where('id_reg', (int) $request->input('id_reg'))
                    ->where('status', Status::Active->value),
            ],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'email' => 'El campo :attribute debe ser un correo válido.',
            'max.string' => 'El campo :attribute no debe superar :max caracteres.',
            'dni.unique' => 'Ya existe un customer registrado con ese dni.',
            'email.unique' => 'Ya existe un customer registrado con ese email.',
            'id_reg.exists' => 'La región indicada no existe o no está activa.',
            'id_com.exists' => 'La comuna indicada no existe, no está activa o no pertenece a la región.',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Datos de registro inválidos', $validator->errors(), 422);
        }

        return $next($request);
    }
}
