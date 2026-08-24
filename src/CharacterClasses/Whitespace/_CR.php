<?php

declare(strict_types=1);

namespace Slurp\CharacterClasses\Whitespace;

final class _CR extends AbstractWhitespace
{
    public function __construct()
    {
        parent::__construct(0x0D);
    }
}
