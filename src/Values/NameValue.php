<?php

declare(strict_types=1);

namespace Slurp\Values;

final readonly class NameValue implements Value
{
    public function __construct(
        public string $value,
    ) {
    }
}
