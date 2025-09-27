<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\EmailController;
use App\Http\Controllers\SecurityTrashController;
use OpenAI\Laravel\Facades\OpenAI;

Route::middleware('api')->prefix('api')->group(function () {
    // OpenAI test
    Route::get('emails/test-ai', [EmailController::class, 'testAI']);
    
    // Classify Emails using OpenAI
    Route::post('emails/classify', [EmailController::class, 'classify']);
    
    // Classify Emails manually by user
    Route::put('emails/{id}/updateLabelManually', [EmailController::class, 'updateLabelManually']);
    
    
    // Retrieve emails by OpenAI classification (REVIEW, KEEP, DELETE) (delete_at -> showDelete_at)
    Route::get('emails/showReview', [EmailController::class, 'showReview']);
    Route::get('emails/showKeep', [EmailController::class, 'showKeep']);
    Route::get('emails/showDelete', [EmailController::class, 'showDelete']);
    Route::get('emails/showDelete_at', [SecurityTrashController::class, 'showDelete_at']);
    
    // Restore an email from Security Trash (mark it again as REVIEW)
    Route::post('emails/restore', [SecurityTrashController::class, 'restore']);
    
    // Permanently delete emails from Security Trash
    Route::delete('emails/destroyDefinitely', [SecurityTrashController::class, 'destroyDefinitely']);
    
    //  Standard RESTful CRUD routes (index, show, store, update, destroy)
    Route::apiResource('emails', EmailController::class);
       
});

//  Make token visible for testing in Postman 
Route::get('/csrf-token', function () {
    return ['csrf_token' => csrf_token()];
});




