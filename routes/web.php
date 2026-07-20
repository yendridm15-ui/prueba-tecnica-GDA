<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return view('docs');
});

Route::get('/docs/openapi.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), ['Content-Type' => 'text/yaml']);
});
