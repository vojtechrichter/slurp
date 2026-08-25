<?php

declare(strict_types=1);

namespace Slurp\Values;

final readonly class ArrayValue implements Value
{
    /**
     * @param list<Value> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
