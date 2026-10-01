<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Exceptions;

class TelegramInvalidParseMode extends TelegramException
{
    public static function make(string $parseMode): self
    {
        return new self("Invalid Telegram parse mode [{$parseMode}]. Use HTML, Markdown or MarkdownV2.");
    }
}
