<?php

declare(strict_types=1);

namespace Slurp\FileBlocks;

final class Header extends AbstractFileBlock
{
    public function __construct(
        private readonly Version $version,
    ) {
    }

    public function getVersionHeader(): string
    {
        return sprintf("%%PDF-%s", $this->version->value);
    }
}
