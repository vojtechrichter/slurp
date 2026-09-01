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
        // TODO: branch on the current byte to scan real tokens (names,
        // numbers, strings, delimiters, keywords)
        $start = $this->offset;
        $byte = $this->content[$this->offset];
        $this->offset++;

        return new Token(Type::Keyword, $byte, $start);
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
