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
        // Single translation endpoints
        Route::post('/translate/claude', [TranslationController::class, 'translateWithClaude']);
        Route::post('/translate/chatgpt', [TranslationController::class, 'translateWithChatGpt']);
        Route::post('/ask/claude', [TranslationController::class, 'askClaude']);

        // Batch translation endpoints (50% discount)
        Route::post('/batch/claude', [TranslationController::class, 'createClaudeBatch']);
        Route::post('/batch/chatgpt', [TranslationController::class, 'createChatGptBatch']);
        Route::get('/batch/{provider}/{batchId}/status', [TranslationController::class, 'getBatchStatus']);
        Route::get('/batch/{provider}/{batchId}/results', [TranslationController::class, 'getBatchResults']);
        Route::post('/batch/{provider}/{batchId}/cancel', [TranslationController::class, 'cancelBatch']);
        Route::get('/batch/{provider}', [TranslationController::class, 'listBatches']);
    });
});