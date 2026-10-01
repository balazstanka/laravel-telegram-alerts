<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Tests;

use BalazsTanka\TelegramAlerts\TelegramAlertsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TelegramAlertsServiceProvider::class,
        ];
    }
}
