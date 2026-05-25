<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the exception handler implementation to our App\Exceptions\Handler
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureHttpsUrls();

        // Register model observers
        \App\Models\Stock::observe(\App\Observers\StockObserver::class);
    }

    /**
     * Evita mixed-content (HTTPS na página, HTTP no spec Swagger/Redoc).
     */
    private function configureHttpsUrls(): void
    {
        $appUrl = (string) config('app.url');
        $forceHttps = filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOL)
            || str_starts_with($appUrl, 'https://');

        $this->app->booted(function () use ($forceHttps): void {
            if ($forceHttps || request()->secure()) {
                URL::forceScheme('https');
            }
        });
    }
}
