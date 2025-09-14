<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\EmailController;

Route::middleware('api')->prefix('api')->group(function () {
    Route::apiResource('emails', EmailController::class);
});

//  Visibilizar el token SOLO PARA PRUEBAS CON POSTMAN
Route::get('/csrf-token', function () {
    return ['csrf_token' => csrf_token()];
});

