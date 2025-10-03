<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranslationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Protected API routes - require Bearer token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('v1')->group(function () {
        Route::post('/translate/claude', [TranslationController::class, 'translateWithClaude']);
        Route::post('/translate/chatgpt', [TranslationController::class, 'translateWithChatGpt']);
        Route::post('/ask/claude', [TranslationController::class, 'askClaude']);
    });
});