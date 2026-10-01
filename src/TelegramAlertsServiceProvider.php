<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts;

use Illuminate\Support\ServiceProvider;

class TelegramAlertsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-telegram-alerts.php', 'laravel-telegram-alerts');

        $this->app->singleton(TelegramAlerts::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-telegram-alerts.php' => config_path('laravel-telegram-alerts.php'),
        ], ['laravel-telegram-alerts', 'laravel-telegram-alerts-config']);
    }
}
