<?php

declare(strict_types=1);

namespace Slurp;

final readonly class PdfResource
{
    private function __construct(
        public string $filename,
        private string $content,
    ) {
    }

    public static function from(string $filename): self
    {
        $content = @file_get_contents($filename);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Failed to read file "%s"', $filename));
        }

        return new self($filename, $content);
    }

    public function getRawContent(): string
    {
        return $this->content;
    }
}
