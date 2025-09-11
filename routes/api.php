<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

Route::apiResource('emails', EmailController::class);
