<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Exceptions;

use Illuminate\Http\Client\Response;

class TelegramApiException extends TelegramException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?int $retryAfter = null,
        public readonly bool $retryable = false,
    ) {
        parent::__construct($message, $status ?? 0);
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $description = $response->json('description');
        $retryAfter = $response->json('parameters.retry_after');

        return new self(
            message: 'Telegram API error ['.$status.']: '.(is_string($description) ? $description : 'unknown error'),
            status: $status,
            retryAfter: is_int($retryAfter) ? $retryAfter : null,
            retryable: $status === 429 || $status >= 500,
        );
    }

    public static function connectionFailed(string $maskedMessage): self
    {
        return new self('Could not reach the Telegram API: '.$maskedMessage, retryable: true);
    }
}
