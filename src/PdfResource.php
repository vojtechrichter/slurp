<?php

declare(strict_types=1);

namespace Slurp;

final readonly class PdfResource implements \Stringable
{
    public function __construct(
        private(set) string $filename,
    ) {
    }

    public static function from(string $filename): self
    {
        return new self($filename);
    }

    public function getRawContent(): string
    {
        return file_get_contents($this->filename);
    }

    public function __toString(): string
    {
        return $this->getRawContent();
    }
}
