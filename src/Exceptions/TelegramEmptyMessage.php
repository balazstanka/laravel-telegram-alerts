<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Exceptions;

class TelegramEmptyMessage extends TelegramException
{
    public static function make(): self
    {
        return new self('Telegram message is empty.');
    }
}
