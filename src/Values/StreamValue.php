<?php

declare(strict_types=1);

namespace Slurp\Values;

final readonly class StreamValue implements Value
{
    public function __construct(
        public DictionaryValue $dictionary,
        public string $data,
    ) {
    }
}
