<?php

declare(strict_types=1);

namespace Slurp\Values;

final readonly class ReferenceValue implements Value
{
    public function __construct(
        public int $objectNumber,
        public int $generationNumber,
    ) {
    }
}
