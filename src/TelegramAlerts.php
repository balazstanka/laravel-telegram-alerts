<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts;

use BalazsTanka\TelegramAlerts\Enums\ParseMode;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramApiException;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramEmptyMessage;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramException;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramInvalidChat;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramInvalidParseMode;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramMissingToken;
use BalazsTanka\TelegramAlerts\Jobs\SendTelegramMessage;
use BalazsTanka\TelegramAlerts\Services\TelegramClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

class TelegramAlerts
{
    private const int SYNC_ATTEMPTS = 3;

    private ?string $chat = null;

    private ?ParseMode $parseMode = null;

    private bool $silent = false;

    private ?int $threadId = null;

    private int $delaySeconds = 0;

    private bool $sync = false;

    /**
     * A chat name from the config, a chat id or a @channelusername.
     */
    public function to(string|int $chat): self
    {
        $clone = clone $this;
        $clone->chat = (string) $chat;

        return $clone;
    }

    public function parseMode(ParseMode|string|null $parseMode): self
    {
        if (is_string($parseMode)) {
            $parseMode = ParseMode::tryFrom($parseMode) ?? throw TelegramInvalidParseMode::make($parseMode);
        }

        $clone = clone $this;
        $clone->parseMode = $parseMode;

        return $clone;
    }

    public function html(): self
    {
        return $this->parseMode(ParseMode::HTML);
    }

    public function markdown(): self
    {
        return $this->parseMode(ParseMode::MARKDOWNV2);
    }

    public function plainText(): self
    {
        return $this->parseMode(null);
    }

    /**
     * Deliver the message without a notification sound.
     */
    public function silent(bool $silent = true): self
    {
        $clone = clone $this;
        $clone->silent = $silent;

        return $clone;
    }

    /**
     * Send to a topic of a forum supergroup.
     */
    public function inThread(int $threadId): self
    {
        $clone = clone $this;
        $clone->threadId = $threadId;

        return $clone;
    }

    public function delayMinutes(int $minutes): self
    {
        $clone = clone $this;
        $clone->delaySeconds += $minutes * 60;

        return $clone;
    }

    public function delayHours(int $hours): self
    {
        return $this->delayMinutes($hours * 60);
    }

    /**
     * Send immediately instead of through the queue.
     */
    public function sync(bool $sync = true): self
    {
        $clone = clone $this;
        $clone->sync = $sync;

        return $clone;
    }

    /**
     * Escape user provided text before putting it into a formatted message.
     */
    public function escape(string $text, ParseMode $parseMode = ParseMode::HTML): string
    {
        return $parseMode->escape($text);
    }

    /**
     * Send the message.
     *
     * Invalid input or configuration throws a TelegramException, except in
     * production, where it is logged so an alert never breaks the request.
     */
    public function message(string $text): void
    {
        if (! config('laravel-telegram-alerts.enabled')) {
            return;
        }

        try {
            $payload = $this->buildPayload($text);

            $this->sync ? $this->sendNow($payload) : $this->dispatch($payload);
        } catch (TelegramException $e) {
            if (! app()->isProduction()) {
                throw $e;
            }

            Log::error('Telegram alert could not be sent.', [
                'chat' => $this->chat ?? 'default',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $text): array
    {
        if (trim($text) === '') {
            throw TelegramEmptyMessage::make();
        }

        $token = config('laravel-telegram-alerts.bot_token');

        if (! is_string($token) || $token === '') {
            throw TelegramMissingToken::make();
        }

        $chatId = $this->resolveChatId();

        $prefix = config('laravel-telegram-alerts.prefix');

        if (is_string($prefix) && $prefix !== '') {
            $prefix = "[{$prefix}] ";
            $text = ($this->parseMode?->escape($prefix) ?? $prefix).$text;
        }

        return array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $this->parseMode?->value,
            'message_thread_id' => $this->threadId,
            'disable_notification' => $this->silent ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function resolveChatId(): string
    {
        $chats = config('laravel-telegram-alerts.chats');
        $chats = is_array($chats) ? $chats : [];
        $name = $this->chat ?? 'default';

        if (array_key_exists($name, $chats)) {
            $chatId = $chats[$name];

            if (! is_scalar($chatId) || (string) $chatId === '') {
                throw TelegramInvalidChat::missing($name);
            }

            return (string) $chatId;
        }

        if ($this->chat === null) {
            throw TelegramInvalidChat::missing($name);
        }

        return $this->chat;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatch(array $payload): void
    {
        $job = $this->makeJob($payload);

        if ($this->delaySeconds > 0) {
            $job->delay($this->delaySeconds);
        }

        dispatch($job);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function makeJob(array $payload): SendTelegramMessage
    {
        $jobClass = config('laravel-telegram-alerts.job');

        if (! is_string($jobClass) || ! is_a($jobClass, SendTelegramMessage::class, true)) {
            $jobClass = SendTelegramMessage::class;
        }

        $job = new $jobClass($payload);

        $connection = config('laravel-telegram-alerts.queue_connection');
        $queue = config('laravel-telegram-alerts.queue');

        if (is_string($connection) && $connection !== '') {
            $job->onConnection($connection);
        }

        if (is_string($queue) && $queue !== '') {
            $job->onQueue($queue);
        }

        return $job;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendNow(array $payload): void
    {
        $client = app(TelegramClient::class);

        for ($attempt = 1; ; $attempt++) {
            try {
                $client->sendMessage($payload);

                return;
            } catch (TelegramApiException $e) {
                if (! $e->retryable || $attempt >= self::SYNC_ATTEMPTS) {
                    throw $e;
                }

                Sleep::for(min($e->retryAfter ?? $attempt, 5))->seconds();
            }
        }
    }
}
