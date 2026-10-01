<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts to(string|int $chat)
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts parseMode(\BalazsTanka\TelegramAlerts\Enums\ParseMode|string|null $parseMode)
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts html()
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts markdown()
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts plainText()
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts silent(bool $silent = true)
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts inThread(int $threadId)
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts delayMinutes(int $minutes)
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts delayHours(int $hours)
 * @method static \BalazsTanka\TelegramAlerts\TelegramAlerts sync(bool $sync = true)
 * @method static string escape(string $text, \BalazsTanka\TelegramAlerts\Enums\ParseMode $parseMode = \BalazsTanka\TelegramAlerts\Enums\ParseMode::HTML)
 * @method static void message(string $text)
 *
 * @see \BalazsTanka\TelegramAlerts\TelegramAlerts
 */
class TelegramAlerts extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \BalazsTanka\TelegramAlerts\TelegramAlerts::class;
    }
}
