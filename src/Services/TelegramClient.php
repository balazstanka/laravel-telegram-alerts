<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Services;

use BalazsTanka\TelegramAlerts\Exceptions\TelegramApiException;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramMissingToken;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramClient
{
    /**
     * Send a message to the Telegram bot
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws TelegramApiException
     * @throws TelegramMissingToken
     */
    public function sendMessage(array $payload): void
    {
        $response = $this->post('sendMessage', $payload);

        if (isset($payload['parse_mode']) && $this->isParseError($response)) {
            unset($payload['parse_mode']);

            $response = $this->post('sendMessage', $payload);
        }

        if ($response->successful() && $response->json('ok') === true) {
            return;
        }

        throw TelegramApiException::fromResponse($response);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $method, array $payload): Response
    {
        $token = config('laravel-telegram-alerts.bot_token');

        if (! is_string($token) || $token === '') {
            throw TelegramMissingToken::make();
        }

        $apiUrl = config('laravel-telegram-alerts.api_url');
        $timeout = config('laravel-telegram-alerts.timeout');

        try {
            return Http::acceptJson()
                ->timeout(is_numeric($timeout) ? (int) $timeout : 10)
                ->connectTimeout(5)
                ->post(rtrim(is_string($apiUrl) ? $apiUrl : 'https://api.telegram.org', '/')."/bot{$token}/{$method}", $payload);
        } catch (Throwable $e) {
            throw TelegramApiException::connectionFailed(str_replace($token, '***', $e->getMessage()));
        }
    }

    private function isParseError(Response $response): bool
    {
        $description = $response->json('description');

        return $response->status() === 400
            && is_string($description)
            && str_contains($description, "can't parse entities");
    }
}
