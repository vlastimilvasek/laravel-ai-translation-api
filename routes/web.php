<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranslationController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/preklad', function () {
    return view('translate');
})->name('translate.form');

// Web API endpointy (používané formulářem, vyžadují CSRF)
Route::post('/preklad/claude', [TranslationController::class, 'translateWithClaude'])
    ->name('translate.claude');

Route::post('/preklad/chatgpt', [TranslationController::class, 'translateWithChatGpt'])
    ->name('translate.chatgpt');

Route::post('/ask/claude', [TranslationController::class, 'askClaude'])
    ->name('ask.claude');