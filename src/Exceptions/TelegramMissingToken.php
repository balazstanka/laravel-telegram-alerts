<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Exceptions;

class TelegramMissingToken extends TelegramException
{
    public static function make(): self
    {
        return new self('No Telegram bot token is configured. Set TELEGRAM_ALERT_BOT_TOKEN.');
    }
}
