<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\EmailController;
use OpenAI\Laravel\Facades\OpenAI;

Route::middleware('api')->prefix('api')->group(function () {
    // TestAI in Postman
    Route::get('emails/test-ai', [EmailController::class, 'testAI']);

    Route::apiResource('emails', EmailController::class);
    
    Route::get('emails/keep', [EmailController::class, 'keep']);
    Route::get('emails/deleted', [EmailController::class, 'deleted']);
    Route::post('emails/classify', [EmailController::class, 'classify']);
    
    

});

//  Making toke visible for testing in Postman 
Route::get('/csrf-token', function () {
    return ['csrf_token' => csrf_token()];
});




