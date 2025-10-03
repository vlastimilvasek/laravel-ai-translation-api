<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\DashboardController;

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Social login
    Route::get('/auth/{provider}', [SocialLoginController::class, 'redirectToProvider'])->name('social.login');
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'handleProviderCallback'])->name('social.callback');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes (require authentication)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/tokens', [DashboardController::class, 'createToken'])->name('tokens.create');
    Route::delete('/dashboard/tokens/{token}', [DashboardController::class, 'revokeToken'])->name('tokens.revoke');

    // Translation form
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
});