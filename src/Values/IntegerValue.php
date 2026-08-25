<?php

declare(strict_types=1);

namespace Slurp\Values;

final readonly class IntegerValue implements Value
{
    public function __construct(
        public int $value,
    ) {
    }
}
