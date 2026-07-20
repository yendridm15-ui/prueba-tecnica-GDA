<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customerService) {}

    public function store(Request $request): JsonResponse
    {
        $customer = $this->customerService->register($request->only([
            'dni', 'id_reg', 'id_com', 'email', 'name', 'last_name', 'address',
        ]));

        return ApiResponse::success('Customer registrado con éxito', [
            'dni' => $customer->dni,
            'email' => $customer->email,
            'name' => $customer->name,
            'last_name' => $customer->last_name,
            'address' => $customer->address,
            'date_reg' => $customer->date_reg->toDateTimeString(),
            'status' => $customer->status,
            'region' => $customer->region->description,
            'commune' => $customer->commune->description,
        ], 201);
    }

    public function show(Request $request): JsonResponse
    {
        $customer = $this->customerService->findActive(
            $request->input('dni'),
            $request->input('email'),
        );

        if ($customer === null) {
            return ApiResponse::error('Customer no encontrado', null, 404);
        }

        return ApiResponse::success('Customer encontrado', [
            'name' => $customer->name,
            'last_name' => $customer->last_name,
            'address' => $customer->address,
            'region' => $customer->region->description,
            'commune' => $customer->commune->description,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $customer = $this->customerService->delete(
            $request->input('dni'),
            $request->input('email'),
        );

        if ($customer === null) {
            return ApiResponse::error('Registro no existe', null, 404);
        }

        return ApiResponse::success('Customer eliminado con éxito', [
            'dni' => $customer->dni,
            'email' => $customer->email,
            'status' => $customer->status,
        ]);
    }
}
