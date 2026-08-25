<?php

declare(strict_types=1);

namespace Slurp\Tests;

use PHPUnit\Framework\TestCase;
use Slurp\PdfResource;

final class PdfResourceTest extends TestCase
{
    public function testReadsFileContent(): void
    {
        $pdf = PdfResource::from(__DIR__ . '/../samples/1.pdf');

        self::assertStringStartsWith('%PDF-', $pdf->getRawContent());
    }

    public function testThrowsWhenFileCannotBeRead(): void
    {
        $this->expectException(\RuntimeException::class);

        PdfResource::from(__DIR__ . '/does-not-exist.pdf');
    }
}
