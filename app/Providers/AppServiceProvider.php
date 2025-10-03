<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ClaudeApiService;
use App\Services\ChatGptApiService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClaudeApiService::class, function ($app) {
            return new ClaudeApiService();
        });

        $this->app->singleton(ChatGptApiService::class, function ($app) {
            return new ChatGptApiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
