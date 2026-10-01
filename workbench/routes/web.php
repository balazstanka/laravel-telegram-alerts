<?php

use BalazsTanka\TelegramAlerts\Facades\TelegramAlerts;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/telegram-test', function () {
    //
    TelegramAlerts::sync()
        ->html()
        ->message("<b>Test</b> message from the\nworkbench: ".TelegramAlerts::escape('<1 & 2>'.' ..random emoji: 🎫'));

    // TelegramAlerts::sync()
    //     ->markdown()
    //     ->message('*Test* message from the workbench: '.TelegramAlerts::escape('1 + 2 = 3. (Done!)', \BalazsTanka\TelegramAlerts\Enums\ParseMode::MARKDOWNV2));

    // TelegramAlerts::sync()
    //     ->parseMode(\BalazsTanka\TelegramAlerts\Enums\ParseMode::MARKDOWN)
    //     ->message('*Test* message from the workbench: '.TelegramAlerts::escape('snake_case_name', \BalazsTanka\TelegramAlerts\Enums\ParseMode::MARKDOWN));

    return 'Sent';
});
