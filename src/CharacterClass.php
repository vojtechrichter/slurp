<?php

declare(strict_types=1);

namespace Slurp;

final class CharacterClass
{
    private const string WHITESPACE = "\x00\x09\x0A\x0C\x0D\x20";
    private const string DELIMITERS = '()<>[]{}/%';

    public static function isWhitespace(string $byte): bool
    {
        return str_contains(self::WHITESPACE, $byte);
    }

    public static function isDelimiter(string $byte): bool
    {
        return str_contains(self::DELIMITERS, $byte);
    }

    public static function isRegular(string $byte): bool
    {
        return !self::isWhitespace($byte) && !self::isDelimiter($byte);
    }
}
