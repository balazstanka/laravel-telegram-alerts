<div align="center">
    <h1>Laravel Telegram Alerts</h1>
    <p>Send alerts to Telegram from your Laravel app.</p>
    <sub>Based on <a href="https://github.com/laravel/package-skeleton">laravel/package-skeleton</a>, thanks to the Laravel team!</sub>
    <p>
        <a href="https://github.com/balazstanka/laravel-telegram-alerts/actions/workflows/tests.yml"><img src="https://github.com/balazstanka/laravel-telegram-alerts/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
        <a href="https://packagist.org/packages/balazstanka/laravel-telegram-alerts"><img src="https://img.shields.io/packagist/v/balazstanka/laravel-telegram-alerts" alt="Latest Version"></a>
        <a href="https://packagist.org/packages/balazstanka/laravel-telegram-alerts"><img src="https://img.shields.io/packagist/dt/balazstanka/laravel-telegram-alerts" alt="Total Downloads"></a>
        <a href="https://packagist.org/packages/balazstanka/laravel-telegram-alerts"><img src="https://img.shields.io/packagist/php-v/balazstanka/laravel-telegram-alerts" alt="PHP Version"></a>
        <a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/balazstanka/laravel-telegram-alerts" alt="License"></a>
    </p>
</div>

## Installation

You can install the package via Composer:

```bash
composer require balazstanka/laravel-telegram-alerts
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="laravel-telegram-alerts"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="laravel-telegram-alerts-config"
```

Add your bot token (from [@BotFather](https://t.me/BotFather)) and the chat id to your `.env`:

```dotenv
TELEGRAM_ALERT_BOT_TOKEN=123456:ABC-your-token
TELEGRAM_ALERT_CHAT_ID=-1001234567890
```

## Usage

```php
use BalazsTanka\TelegramAlerts\Facades\TelegramAlerts;

TelegramAlerts::message("You have a new subscriber to the {$newsletter->name} newsletter!");
```

The message is sent by a queued job, so your app keeps working when Telegram is slow or down. The job retries server errors with a backoff, waits as long as Telegram asks when it is rate limited, and fails right away on errors that a retry cannot fix (for example an unknown chat or a blocked bot). A failed delivery is logged.

### Sending without the queue

Some alerts must arrive even when the queue itself is broken, for example a queue health check. `sync()` sends the message immediately and retries a few times:

```php
TelegramAlerts::sync()->message('The queue worker is not running!');
```

### Multiple chats

Add named chats to the `chats` array of the config file, then pick one with `to()`. You may also pass a chat id or a `@channelusername`:

```php
TelegramAlerts::to('ops')->message('Disk is almost full');
TelegramAlerts::to(-1001234567890)->message('Hello');
```

### Formatting

Messages are sent as plain text by default. Use `html()` or `markdown()` (MarkdownV2) to format them, and `escape()` for any text you don't control:

```php
TelegramAlerts::html()->message('<b>Payment failed</b>: '.TelegramAlerts::escape($exception->getMessage()));
```

If Telegram cannot parse the formatting, the message is sent again as plain text, so the alert is not lost.

### More options

```php
TelegramAlerts::to('ops')
    ->silent()           // no notification sound
    ->inThread(42)       // a topic in a forum group
    ->delayMinutes(5)    // also: delayHours()
    ->message('Nightly backup finished');
```

### Errors

An empty message, an unknown chat or a missing token throws a `TelegramException`, so you notice it during development. In production these are logged instead, and the request keeps working.

Set `TELEGRAM_ALERT_ENABLED=false` to turn alerts off, for example in CI.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Telegram Alerts! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Balazs Tanka](https://github.com/balazstanka)

## License

Laravel Telegram Alerts is open-sourced software licensed under the MIT license.
