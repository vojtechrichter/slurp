<?php

declare(strict_types=1);

namespace Slurp\Values;

final readonly class RealValue implements Value
{
    public function __construct(
        public float $value,
    ) {
    }
}
