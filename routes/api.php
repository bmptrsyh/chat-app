<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class,'register']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class,'me']);
    Route::post('logout', [AuthController::class,'logout']);

    // Conversation
    Route::post('conversation', [ConversationController::class,'store']);
    Route::get('conversation', [ConversationController::class,'index']);
    Route::get('conversation/{conversation}', [ConversationController::class,'show']);

    // Message
    Route::post('message/{conversation}', [MessageController::class,'store']);
    Route::get('message/{conversation}', [MessageController::class,'index']);
});
