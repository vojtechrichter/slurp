<?php

declare(strict_types=1);

namespace Slurp\CharacterClasses\Whitespace;

abstract class AbstractWhitespace
{
    public function __construct(
        public int $value,
    ) {
    }
}
