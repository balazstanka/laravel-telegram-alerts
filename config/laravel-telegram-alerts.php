<?php

declare(strict_types=1);

use BalazsTanka\TelegramAlerts\Jobs\SendTelegramMessage;

return [

    /*
     * Turn alerts off without touching the calling code, e.g. in local or CI environments.
     */
    'enabled' => env('TELEGRAM_ALERT_ENABLED', true),

    /*
     * The bot token you received from @BotFather. It is only read when the message is actually sent, so it never ends up in the queue payload.
     */
    'bot_token' => env('TELEGRAM_ALERT_BOT_TOKEN'),

    /*
     * Named chats you can send to with TelegramAlerts::to('name').
     * You may also pass a raw chat id (e.g. -1001234567890) or @channelusername.
     */
    'chats' => [
        'default' => env('TELEGRAM_ALERT_CHAT_ID'),
    ],

    /*
     * Optional text placed in front of every message as "[prefix] ",
     * e.g. the app name or environment.
     */
    'prefix' => env('TELEGRAM_ALERT_PREFIX'),

    /*
     * This job sends the message to Telegram. You can extend it to change the number of tries, backoff, timeout, etc.
     */
    'job' => SendTelegramMessage::class,

    /*
     * The queue connection and queue name used to send the alert. When null, the application's defaults are used.
     */
    'queue_connection' => env('TELEGRAM_ALERT_QUEUE_CONNECTION'),

    'queue' => env('TELEGRAM_ALERT_QUEUE'),

    /*
     * The Telegram Bot API base URL. Change it only when you run a local Bot API server.
     */
    'api_url' => env('TELEGRAM_ALERT_API_URL', 'https://api.telegram.org'),

    /*
     * HTTP timeout in seconds for a single request to Telegram.
     */
    'timeout' => 10,

];
