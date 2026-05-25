<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
        $this->configurePublicUrls();

        \App\Models\Stock::observe(\App\Observers\StockObserver::class);
    }

    /**
     * Define URL base da aplicação no código/config (não depende só do proxy/nginx).
     */
    private function configurePublicUrls(): void
    {
        $publicUrl = rtrim((string) config('postos.public_url'), '/');
        if ($publicUrl === '') {
            return;
        }

        Config::set('app.url', $publicUrl);
        URL::forceRootUrl($publicUrl);

        $forceHttps = config('postos.force_https');
        if ($forceHttps === null) {
            $forceHttps = str_starts_with($publicUrl, 'https://');
        }
        if (filter_var($forceHttps, FILTER_VALIDATE_BOOL)) {
            URL::forceScheme('https');
        }

        Config::set('l5-swagger.defaults.constants.L5_SWAGGER_CONST_HOST', $publicUrl);
    }
}
