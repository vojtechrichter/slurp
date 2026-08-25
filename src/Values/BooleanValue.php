<?php

declare(strict_types=1);

namespace Slurp\Values;

final readonly class BooleanValue implements Value
{
    public function __construct(
        public bool $value,
    ) {
    }
}
