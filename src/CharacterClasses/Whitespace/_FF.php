<?php

declare(strict_types=1);

namespace Slurp\CharacterClasses\Whitespace;

class _FF extends AbstractWhitespace
{
    public function __construct()
    {
        parent::__construct(0x0C);
    }
}
