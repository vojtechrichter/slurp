<?php

declare(strict_types=1);

namespace Slurp\CharacterClasses\Whitespace;

final class _Space extends AbstractWhitespace
{
    public function __construct()
    {
        parent::__construct(0x20);
    }
}
