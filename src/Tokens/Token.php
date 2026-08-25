<?php

declare(strict_types=1);

namespace Slurp\Tokens;

final readonly class Token
{
    public function __construct(
        public Type $type,
        public string $lexeme,
        public int $offset,
    ) {
    }
}
