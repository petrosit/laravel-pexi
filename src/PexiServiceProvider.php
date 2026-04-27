<?php

namespace Pexi\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Throwable;

class PexiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pexi.php', 'pexi');

        $this->app->singleton(ExceptionReporter::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/pexi.php' => config_path('pexi.php'),
        ], 'pexi-config');

        $this->app->afterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! method_exists($handler, 'reportable')) {
                return;
            }

            $handler->reportable(function (Throwable $exception): void {
                app(ExceptionReporter::class)->report($exception);
            });
        });
    }
}
