<?php

declare(strict_types=1);

namespace Slurp;

use Slurp\Tokens\Token;
use Slurp\Tokens\Type;

final class Lexer
{
    private int $offset = 0;

    public function __construct(
        private readonly string $content,
    ) {
    }

    public function next(): Token
    {
        while (!$this->isAtEnd()) {
            $byte = $this->content[$this->offset];

            if (CharacterClass::isWhitespace($byte)) {
                $this->offset++;
            } elseif ($byte === '%') {
                $start = $this->offset;
                $comment = $this->skipComment();

                if ($comment === '%%EOF') {
                    return new Token(Type::Eof, $comment, $start);
                }
            } else {
                return $this->scanToken();
            }
        }

        return new Token(Type::Eof, '', $this->offset);
    }

    private function scanToken(): Token
    {
        $start = $this->offset;
        $byte = $this->content[$this->offset];

        $single = match ($byte) {
            '[' => Type::ArrOpen,
            ']' => Type::ArrClose,
            '{' => Type::BraceOpen,
            '}' => Type::BraceClose,
            default => null,
        };

        if ($single !== null) {
            $this->offset++;

            return new Token($single, $byte, $start);
        }

        $pair = substr($this->content, $start, 2);

        if ($pair === '<<') {
            $this->offset += 2;

            return new Token(Type::DictOpen, $pair, $start);
        }

        if ($pair === '>>') {
            $this->offset += 2;

            return new Token(Type::DictClose, $pair, $start);
        }

        if (CharacterClass::isRegular($byte)) {
            $run = $this->scanRegularRun();

            return new Token(self::isNumeric($run) ? Type::Num : Type::Keyword, $run, $start);
        }

        if ($byte === '/') {
            $this->offset++;

            return new Token(Type::Name, self::decodeName($this->scanRegularRun()), $start);
        }

        if ($byte === '(') {
            $this->offset++;

            return new Token(Type::Str, $this->scanLiteralString($start), $start);
        }

        // TODO: hex strings
        $this->offset++;

        return new Token(Type::Keyword, $byte, $start);
    }

    private function scanRegularRun(): string
    {
        $start = $this->offset;

        while (!$this->isAtEnd() && CharacterClass::isRegular($this->content[$this->offset])) {
            $this->offset++;
        }

        return substr($this->content, $start, $this->offset - $start);
    }

    private function scanLiteralString(int $start): string
    {
        $value = '';
        $depth = 1;

        while (!$this->isAtEnd()) {
            $byte = $this->content[$this->offset++];

            if ($byte === '(') {
                $depth++;
            } elseif ($byte === ')') {
                if (--$depth === 0) {
                    return $value;
                }
            } elseif ($byte === '\\') {
                $value .= $this->scanStringEscape();
                continue;
            } elseif ($byte === "\x0D") {
                $this->skipLineFeed();
                $byte = "\x0A";
            }

            $value .= $byte;
        }

        throw new LexerException(sprintf('Unterminated literal string starting at offset %d', $start));
    }

    private function scanStringEscape(): string
    {
        if ($this->isAtEnd()) {
            return '';
        }

        $byte = $this->content[$this->offset++];

        if ($byte === "\x0D") {
            $this->skipLineFeed();
        }

        if (self::isOctalDigit($byte)) {
            $octal = $byte;

            while (strlen($octal) < 3 && !$this->isAtEnd() && self::isOctalDigit($this->content[$this->offset])) {
                $octal .= $this->content[$this->offset++];
            }

            return chr(octdec($octal) & 0xFF);
        }

        return match ($byte) {
            'n' => "\x0A",
            'r' => "\x0D",
            't' => "\x09",
            'b' => "\x08",
            'f' => "\x0C",
            "\x0A", "\x0D" => '',
            default => $byte,
        };
    }

    private static function isOctalDigit(string $byte): bool
    {
        return $byte >= '0' && $byte <= '7';
    }

    private function skipLineFeed(): void
    {
        if (!$this->isAtEnd() && $this->content[$this->offset] === "\x0A") {
            $this->offset++;
        }
    }

    private static function decodeName(string $run): string
    {
        return preg_replace_callback(
            '/#([0-9A-Fa-f]{2})/',
            static fn (array $m): string => pack('H2', $m[1]),
            $run,
        ) ?? $run;
    }

    private static function isNumeric(string $run): bool
    {
        return preg_match('/^[+-]?(\d+\.?\d*|\.\d+)$/', $run) === 1;
    }

    private function skipComment(): string
    {
        $start = $this->offset;

        while (!$this->isAtEnd() && $this->content[$this->offset] !== "\x0A" && $this->content[$this->offset] !== "\x0D") {
            $this->offset++;
        }

        return substr($this->content, $start, $this->offset - $start);
    }

    private function isAtEnd(): bool
    {
        return $this->offset >= strlen($this->content);
    }
}
