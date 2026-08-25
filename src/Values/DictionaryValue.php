<?php

declare(strict_types=1);

namespace Slurp\Values;

final readonly class DictionaryValue implements Value
{
    /**
     * @param array<string, Value> $entries keyed by name without the leading slash
     */
    public function __construct(
        public array $entries,
    ) {
    }
}
