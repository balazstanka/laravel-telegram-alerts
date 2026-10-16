# Release Notes

## [Unreleased](https://github.com/balazstanka/laravel-telegram-alerts/compare/v1.1.0...HEAD)

## [v1.1.0](https://github.com/balazstanka/laravel-telegram-alerts/compare/v1.0.0...v1.1.0) - 2026-10-16

<!-- Release notes generated using configuration in .github/release.yml at main -->
### What's Changed

Add inline keyboard link buttons with `addUrl($text, $url)`. See the README for details.

#### Enhancements

* feat: add inline keyboard URL buttons with addUrl() by @balazstanka in https://github.com/balazstanka/laravel-telegram-alerts/pull/2

#### Other Changes

* Bump shivammathur/setup-php from 2.37.2 to 2.40.0 by @dependabot[bot] in https://github.com/balazstanka/laravel-telegram-alerts/pull/1

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/balazstanka/laravel-telegram-alerts/pull/1
* @balazstanka made their first contribution in https://github.com/balazstanka/laravel-telegram-alerts/pull/2

**Full Changelog**: https://github.com/balazstanka/laravel-telegram-alerts/compare/v1.0.0...v1.1.0

## [v1.0.0](https://github.com/balazstanka/laravel-telegram-alerts/compare/v1.0.0...v1.0.0) - 2026-10-01

Initial release

### Features

- Send Telegram messages with one line: `TelegramAlerts::message('Hello')`
- Messages are sent in the background through the queue
- Failed messages are retried automatically
- Use `sync()` to send right away, without the queue
- Send to different chats, channels or forum topics
- Format messages with HTML or Markdown, and escape text you don't control
- If the formatting is broken, the message is still sent as plain text
- Add a prefix to every message, for example the app name
- Send silently or with a delay
- Your bot token is never saved in the queue or shown in error messages

### Requirements

- PHP 8.3+
- Laravel 12 or 13

**Full Changelog**: https://github.com/balazstanka/laravel-telegram-alerts/commits/v1.0.0
