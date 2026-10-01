<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Exceptions;

class TelegramInvalidChat extends TelegramException
{
    public static function missing(string $name): self
    {
        return new self("No Telegram chat id is configured for [{$name}]. Set TELEGRAM_ALERT_CHAT_ID or the [chats.{$name}] config value.");
    }
}
