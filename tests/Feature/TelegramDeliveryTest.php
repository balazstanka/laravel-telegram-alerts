<?php

declare(strict_types=1);

use BalazsTanka\TelegramAlerts\Exceptions\TelegramApiException;
use BalazsTanka\TelegramAlerts\Facades\TelegramAlerts;
use BalazsTanka\TelegramAlerts\Jobs\SendTelegramMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();

    config()->set('laravel-telegram-alerts.bot_token', 'secret-token');
    config()->set('laravel-telegram-alerts.chats.default', '111');
});

function telegramError(int $status, string $description, array $parameters = []): array
{
    return ['ok' => false, 'error_code' => $status, 'description' => $description] + ($parameters === [] ? [] : ['parameters' => $parameters]);
}

function runJob(array $payload = ['chat_id' => '111', 'text' => 'Hello']): SendTelegramMessage
{
    $job = (new SendTelegramMessage($payload))->withFakeQueueInteractions();

    app()->call([$job, 'handle']);

    return $job;
}

it('posts the payload to the Telegram API', function () {
    Http::fake(['*' => Http::response(['ok' => true, 'result' => []])]);

    TelegramAlerts::sync()->html()->message('<b>Hello</b>');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.telegram.org/botsecret-token/sendMessage'
        && $request['chat_id'] === '111'
        && $request['text'] === '<b>Hello</b>'
        && $request['parse_mode'] === 'HTML');
});

it('resends as plain text when Telegram cannot parse the formatting', function () {
    Http::fake(['*' => Http::sequence()
        ->push(telegramError(400, "Bad Request: can't parse entities: Unsupported start tag"), 400)
        ->push(['ok' => true, 'result' => []]),
    ]);

    TelegramAlerts::sync()->html()->message('<oops>');

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => ! isset($request['parse_mode']) && $request['text'] === '<oops>');
});

it('retries a sync message on server errors', function () {
    Http::fake(['*' => Http::sequence()
        ->push(telegramError(502, 'Bad Gateway'), 502)
        ->push(['ok' => true, 'result' => []]),
    ]);

    TelegramAlerts::sync()->message('Hello');

    Http::assertSentCount(2);
    Sleep::assertSleptTimes(1);
});

it('waits as long as Telegram asks when rate limited in sync mode', function () {
    Http::fake(['*' => Http::sequence()
        ->push(telegramError(429, 'Too Many Requests: retry after 3', ['retry_after' => 3]), 429)
        ->push(['ok' => true, 'result' => []]),
    ]);

    TelegramAlerts::sync()->message('Hello');

    Sleep::assertSequence([Sleep::for(3)->seconds()]);
});

it('releases the job for as long as Telegram asks when rate limited', function () {
    Http::fake(['*' => Http::response(telegramError(429, 'Too Many Requests: retry after 30', ['retry_after' => 30]), 429)]);

    runJob()->assertReleased(delay: 30);
});

it('throws on server errors so the queue retries the job', function () {
    Http::fake(['*' => Http::response(telegramError(500, 'Internal Server Error'), 500)]);

    runJob();
})->throws(TelegramApiException::class, 'Telegram API error [500]');

it('fails the job right away when the error cannot be fixed by retrying', function (int $status, string $description) {
    Http::fake(['*' => Http::response(telegramError($status, $description), $status)]);

    runJob()->assertFailed();
})->with([
    'chat not found' => [400, 'Bad Request: chat not found'],
    'bot blocked' => [403, 'Forbidden: bot was blocked by the user'],
]);

it('fails the job right away when the token is missing', function () {
    config()->set('laravel-telegram-alerts.bot_token', null);
    Http::fake();

    runJob()->assertFailed();
    Http::assertNothingSent();
});

it('never leaks the bot token in connection errors', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out for https://api.telegram.org/botsecret-token/sendMessage'));

    try {
        runJob();
    } catch (TelegramApiException $e) {
        expect($e->getMessage())->not->toContain('secret-token')
            ->and($e->getPrevious())->toBeNull()
            ->and($e->retryable)->toBeTrue();

        return;
    }

    test()->fail('Expected a TelegramApiException.');
});
