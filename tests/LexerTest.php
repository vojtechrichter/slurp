<?php

declare(strict_types=1);

namespace Slurp\Tests;

use PHPUnit\Framework\TestCase;
use Slurp\Lexer;
use Slurp\Tokens\Type;

final class LexerTest extends TestCase
{
    public function testReturnsEofOnEmptyInput(): void
    {
        $lexer = new Lexer('');

        self::assertSame(Type::Eof, $lexer->next()->type);
    }

    public function testReturnsEofOnWhitespaceOnlyInput(): void
    {
        $lexer = new Lexer(" \r\n\t");

        self::assertSame(Type::Eof, $lexer->next()->type);
    }

    public function testSkipsLeadingWhitespace(): void
    {
        $token = new Lexer("  \t\n x")->next();

        self::assertSame('x', $token->lexeme);
        self::assertSame(5, $token->offset);
    }

    public function testSkipsCommentToEndOfLine(): void
    {
        $token = new Lexer("%PDF-1.4\nx")->next();

        self::assertSame('x', $token->lexeme);
        self::assertSame(9, $token->offset);
    }

    public function testSkipsCommentEndedByCarriageReturnAlone(): void
    {
        self::assertSame('x', new Lexer("%comment\rx")->next()->lexeme);
    }

    public function testSkipsConsecutiveCommentsAndWhitespace(): void
    {
        self::assertSame('x', new Lexer("%a\n  %b\n  x")->next()->lexeme);
    }

    public function testReturnsEofWhenCommentReachesEndOfInput(): void
    {
        self::assertSame(Type::Eof, new Lexer('%%EOF')->next()->type);
    }

    public function testReturnsEofTokenAtEofMarker(): void
    {
        $lexer = new Lexer("x\n%%EOF\ny");
        $lexer->next();

        $token = $lexer->next();

        self::assertSame(Type::Eof, $token->type);
        self::assertSame('%%EOF', $token->lexeme);
        self::assertSame(2, $token->offset);
    }

    public function testCommentMerelyStartingWithEofMarkerIsSkipped(): void
    {
        self::assertSame('x', new Lexer("%%EOFbut-not-really\nx")->next()->lexeme);
    }
}
