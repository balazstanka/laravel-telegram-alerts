<?php

declare(strict_types=1);

namespace BalazsTanka\TelegramAlerts\Enums;

enum ParseMode: string
{
    case MARKDOWNV2 = 'MarkdownV2';
    case MARKDOWN = 'Markdown';
    case HTML = 'HTML';

    private const array MARKDOWN_V2_SPECIAL_CHARACTERS = [
        '\\',
        '_',
        '*',
        '[',
        ']',
        '(',
        ')',
        '~',
        '`',
        '>',
        '#',
        '+',
        '-',
        '=',
        '|',
        '{',
        '}',
        '.',
        '!',
    ];

    private const array MARKDOWN_SPECIAL_CHARACTERS = ['_', '*', '`', '['];

    /**
     * Escape user provided text so it is shown literally in this parse mode.
     */
    public function escape(string $text): string
    {
        return match ($this) {
            self::HTML => htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            self::MARKDOWNV2 => self::putBackslashBefore(self::MARKDOWN_V2_SPECIAL_CHARACTERS, $text),
            self::MARKDOWN => self::putBackslashBefore(self::MARKDOWN_SPECIAL_CHARACTERS, $text),
        };
    }

    /**
     * @param  list<string>  $characters
     */
    private static function putBackslashBefore(array $characters, string $text): string
    {
        $replacements = [];

        foreach ($characters as $character) {
            $replacements[$character] = '\\'.$character;
        }

        return strtr($text, $replacements);
    }
}
