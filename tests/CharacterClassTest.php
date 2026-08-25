<?php

declare(strict_types=1);

namespace Slurp\Tests;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Slurp\CharacterClass;

final class CharacterClassTest extends TestCase
{
    #[TestWith(["\x00"])]
    #[TestWith(["\x09"])]
    #[TestWith(["\x0A"])]
    #[TestWith(["\x0C"])]
    #[TestWith(["\x0D"])]
    #[TestWith(["\x20"])]
    public function testRecognizesWhitespaceBytes(string $byte): void
    {
        self::assertTrue(CharacterClass::isWhitespace($byte));
    }

    #[TestWith(['('])]
    #[TestWith([')'])]
    #[TestWith(['<'])]
    #[TestWith(['>'])]
    #[TestWith(['['])]
    #[TestWith([']'])]
    #[TestWith(['{'])]
    #[TestWith(['}'])]
    #[TestWith(['/'])]
    #[TestWith(['%'])]
    public function testRecognizesDelimiterBytes(string $byte): void
    {
        self::assertTrue(CharacterClass::isDelimiter($byte));
    }

    #[TestWith(['a'])]
    #[TestWith(['7'])]
    #[TestWith(['+'])]
    public function testRegularBytesAreNeitherWhitespaceNorDelimiter(string $byte): void
    {
        self::assertFalse(CharacterClass::isWhitespace($byte));
        self::assertFalse(CharacterClass::isDelimiter($byte));
    }
}
