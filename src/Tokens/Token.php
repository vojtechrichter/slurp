<?php

declare(strict_types=1);

namespace Slurp\Tokens;

abstract class Token
{
    public function __construct(
        protected string $value,
        protected int $length,
    ) {
    }
}
