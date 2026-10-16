<?php

declare(strict_types=1);

use BalazsTanka\TelegramAlerts\Enums\ParseMode;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramEmptyMessage;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramInvalidChat;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramInvalidParseMode;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramMissingToken;
use BalazsTanka\TelegramAlerts\Exceptions\TelegramUrlTextAlreadyExists;
use BalazsTanka\TelegramAlerts\Facades\TelegramAlerts;
use BalazsTanka\TelegramAlerts\Jobs\SendTelegramMessage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Bus::fake();

    config()->set('laravel-telegram-alerts.bot_token', 'test-token');
    config()->set('laravel-telegram-alerts.chats', [
        'default' => '111',
        'ops' => '-100222',
    ]);
});

it('dispatches a plain text message to the default chat', function () {
    TelegramAlerts::message('Deploy finished');

    Bus::assertDispatched(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->payload === [
        'chat_id' => '111',
        'text' => 'Deploy finished',
    ]);
});

it('sends to a named chat, a raw chat id or a channel username', function (string|int $to, string $chatId) {
    TelegramAlerts::to($to)->message('Hello');

    Bus::assertDispatched(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->payload['chat_id'] === $chatId);
})->with([
    'named chat' => ['ops', '-100222'],
    'raw id' => [-100333, '-100333'],
    'channel' => ['@my_channel', '@my_channel'],
]);

it('does not share state between alerts', function () {
    $ops = TelegramAlerts::to('ops')->silent();

    $ops->message('First');
    TelegramAlerts::message('Second');

    Bus::assertDispatched(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->payload === [
        'chat_id' => '111',
        'text' => 'Second',
    ]);
});

it('adds the parse mode, thread and silent options', function () {
    TelegramAlerts::to('ops')->html()->inThread(42)->silent()->message('<b>Down</b>');

    Bus::assertDispatched(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->payload === [
        'chat_id' => '-100222',
        'text' => '<b>Down</b>',
        'parse_mode' => 'HTML',
        'message_thread_id' => 42,
        'disable_notification' => true,
    ]);
});

it('accepts the parse mode as a string or an enum', function (ParseMode|string $parseMode) {
    TelegramAlerts::parseMode($parseMode)->message('Hello');

    Bus::assertDispatched(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->payload['parse_mode'] === 'Markdown');
})->with(['Markdown', ParseMode::MARKDOWN]);

it('puts the escaped prefix in front of the message', function () {
    config()->set('laravel-telegram-alerts.prefix', 'production');

    TelegramAlerts::markdown()->message('*Down*');

    Bus::assertDispatched(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->payload['text'] === '\[production\] *Down*');
});

it('escapes text for each parse mode', function (ParseMode $parseMode, string $expected) {
    expect(TelegramAlerts::escape('a<b & [c]_d.', $parseMode))->toBe($expected);
})->with([
    'html' => [ParseMode::HTML, 'a&lt;b &amp; [c]_d.'],
    'markdown v2' => [ParseMode::MARKDOWNV2, 'a<b & \[c\]\_d\.'],
    'markdown' => [ParseMode::MARKDOWN, 'a<b & \[c]\_d.'],
]);

it('delays the message', function () {
    TelegramAlerts::delayHours(1)->delayMinutes(10)->message('Later');

    Bus::assertDispatched(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->delay === 4200);
});

it('uses the configured queue connection and queue', function () {
    config()->set('laravel-telegram-alerts.queue_connection', 'redis');
    config()->set('laravel-telegram-alerts.queue', 'alerts');

    TelegramAlerts::message('Queued');

    Bus::assertDispatched(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->connection === 'redis' && $job->queue === 'alerts');
});

it('does nothing when alerts are disabled', function () {
    config()->set('laravel-telegram-alerts.enabled', false);

    TelegramAlerts::message('Hello');

    Bus::assertNothingDispatched();
});

describe('invalid input', function () {
    it('throws when the message is empty', function () {
        TelegramAlerts::message('  ');
    })->throws(TelegramEmptyMessage::class);

    it('throws when the bot token is missing', function () {
        config()->set('laravel-telegram-alerts.bot_token', null);

        TelegramAlerts::message('Hello');
    })->throws(TelegramMissingToken::class);

    it('throws when the default chat id is missing', function () {
        config()->set('laravel-telegram-alerts.chats.default', null);

        TelegramAlerts::message('Hello');
    })->throws(TelegramInvalidChat::class);

    it('throws when the parse mode is invalid', function () {
        TelegramAlerts::parseMode('HTMllL');
    })->throws(TelegramInvalidParseMode::class);

    it('logs instead of throwing in production', function () {
        app()->detectEnvironment(fn (): string => 'production');
        Log::spy();

        TelegramAlerts::message('  ');

        Log::shouldHaveReceived('error')->once();
        Bus::assertNothingDispatched();
    });
});

it('can send url with text', function () {
    TelegramAlerts::addUrl('button', 'hello.com')->message('hello');

    Bus::assertDispatched(SendTelegramMessage::class, function ($job) {
        $payload = $job->payload;

        return array_key_exists('reply_markup', $payload) && $payload['reply_markup']->inline_keyboard[0][0]['text'] === 'button';
    });
});

it('can skip empty text or url', function () {
    TelegramAlerts::addUrl('', 'hello.com')->message('hello');

    Bus::assertDispatched(SendTelegramMessage::class, function ($job) {
        $payload = $job->payload;

        return ! array_key_exists('reply_markup', $payload);
    });
});

it('can keep the correct text and url', function () {
    TelegramAlerts::addUrl('', 'hello.com')->addUrl('ok', 'ok.com')->message('hello');

    Bus::assertDispatched(SendTelegramMessage::class, function ($job) {
        $payload = $job->payload;

        return array_key_exists('reply_markup', $payload) && $payload['reply_markup']->inline_keyboard[0][0]['text'] === 'ok';
    });
});

it('throw exception if the url text already exists', function () {
    TelegramAlerts::addUrl('testButton', 'hello.com')->addUrl('testButton', 'hello.com')->message('hello');
})->throws(TelegramUrlTextAlreadyExists::class);
