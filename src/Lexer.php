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

        // TODO: names, strings
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
