<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Jobs;

use BalazsTanka\TelegramAlerts\Exceptions\TelegramApiException;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramMissingToken;
use BalazsTanka\TelegramAlerts\Services\TelegramClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 8;

    public int $timeout = 30;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly array $payload) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [5, 15, 30, 60, 120, 300];
    }

    public function handle(TelegramClient $client): void
    {
        try {
            $client->sendMessage($this->payload);
        } catch (TelegramMissingToken $e) {
            $this->fail($e);
        } catch (TelegramApiException $e) {
            if ($e->retryAfter !== null) {
                $this->release($e->retryAfter);

                return;
            }

            if (! $e->retryable) {
                $this->fail($e);

                return;
            }

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Telegram alert could not be delivered.', [
            'chat_id' => $this->payload['chat_id'] ?? null,
            'error' => $exception?->getMessage(),
        ]);
    }
}
