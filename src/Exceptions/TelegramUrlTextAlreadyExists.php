<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Exceptions;

class TelegramUrlTextAlreadyExists extends TelegramException
{
    public static function make(string $text): self
    {
        return new self("A Telegram url button with the text [{$text}] already exists.");
    }
}
