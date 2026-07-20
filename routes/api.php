<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Middleware\ValidateCustomerIdentifier;
use App\Http\Middleware\ValidateCustomerRegistration;
use App\Http\Middleware\ValidateLoginRequest;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware([ValidateLoginRequest::class, 'throttle:10,1'])
    ->name('api.login');

Route::middleware('auth.token')->group(function () {
    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware(ValidateCustomerRegistration::class)
        ->name('api.customers.store');

    Route::get('/customers', [CustomerController::class, 'show'])
        ->middleware(ValidateCustomerIdentifier::class)
        ->name('api.customers.show');

    Route::delete('/customers', [CustomerController::class, 'destroy'])
        ->middleware(ValidateCustomerIdentifier::class)
        ->name('api.customers.destroy');
});
