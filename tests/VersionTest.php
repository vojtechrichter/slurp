<?php

declare(strict_types=1);

namespace Slurp\Tests;

use PHPUnit\Framework\TestCase;
use Slurp\Version;

final class VersionTest extends TestCase
{
    public function testParsesVersionFromHeaderLine(): void
    {
        self::assertSame(Version::V17, Version::fromHeader('%PDF-1.7'));
    }

    public function testParsesVersionFromLeadingBytesOfFileContent(): void
    {
        self::assertSame(Version::V14, Version::fromHeader("%PDF-1.4\n1 0 obj"));
    }

    public function testSupportsPdf20(): void
    {
        self::assertSame(Version::V20, Version::fromHeader('%PDF-2.0'));
    }

    public function testRejectsContentWithoutPdfHeader(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Version::fromHeader('not a pdf');
    }

    public function testRejectsUnknownVersionNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Version::fromHeader('%PDF-9.9');
    }
}
