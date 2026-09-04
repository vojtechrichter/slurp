<?php

declare(strict_types=1);

namespace Slurp\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slurp\Lexer;
use Slurp\LexerException;
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

    #[DataProvider('delimiterProvider')]
    public function testScansSingleDelimiter(string $input, Type $type): void
    {
        $token = new Lexer($input)->next();

        self::assertSame($type, $token->type);
        self::assertSame($input, $token->lexeme);
        self::assertSame(0, $token->offset);
    }

    /**
     * @return iterable<string, array{string, Type}>
     */
    public static function delimiterProvider(): iterable
    {
        yield 'array open' => ['[', Type::ArrOpen];
        yield 'array close' => [']', Type::ArrClose];
        yield 'brace open' => ['{', Type::BraceOpen];
        yield 'brace close' => ['}', Type::BraceClose];
        yield 'dict open' => ['<<', Type::DictOpen];
        yield 'dict close' => ['>>', Type::DictClose];
    }

    public function testScansAdjacentDelimitersAsSeparateTokens(): void
    {
        $lexer = new Lexer('[<<>>]');

        self::assertSame(Type::ArrOpen, $lexer->next()->type);
        self::assertSame(Type::DictOpen, $lexer->next()->type);
        self::assertSame(Type::DictClose, $lexer->next()->type);
        self::assertSame(Type::ArrClose, $lexer->next()->type);
        self::assertSame(Type::Eof, $lexer->next()->type);
    }

    public function testDictCloseOffsetPointsAtFirstByte(): void
    {
        $lexer = new Lexer('<< >>');
        $lexer->next();

        self::assertSame(3, $lexer->next()->offset);
    }

    public function testScansKeywordRunUntilWhitespace(): void
    {
        $token = new Lexer("obj endobj")->next();

        self::assertSame(Type::Keyword, $token->type);
        self::assertSame('obj', $token->lexeme);
        self::assertSame(0, $token->offset);
    }

    public function testKeywordRunStopsAtDelimiter(): void
    {
        $lexer = new Lexer('true]');

        self::assertSame('true', $lexer->next()->lexeme);
        self::assertSame(Type::ArrClose, $lexer->next()->type);
    }

    public function testKeywordRunStopsAtEndOfInput(): void
    {
        self::assertSame('null', new Lexer('null')->next()->lexeme);
    }

    #[DataProvider('numberProvider')]
    public function testClassifiesNumericRunAsNum(string $input): void
    {
        $token = new Lexer($input)->next();

        self::assertSame(Type::Num, $token->type);
        self::assertSame($input, $token->lexeme);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function numberProvider(): iterable
    {
        yield 'integer' => ['42'];
        yield 'negative integer' => ['-3'];
        yield 'explicit plus' => ['+1'];
        yield 'real' => ['3.14'];
        yield 'real without leading digit' => ['-.002'];
        yield 'real with trailing dot' => ['6.'];
    }

    #[DataProvider('nonNumberProvider')]
    public function testClassifiesNonNumericRunAsKeyword(string $input): void
    {
        self::assertSame(Type::Keyword, new Lexer($input)->next()->type);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonNumberProvider(): iterable
    {
        yield 'two dots' => ['1.2.3'];
        yield 'double sign' => ['--5'];
        yield 'lone dot' => ['.'];
        yield 'lone sign' => ['-'];
        yield 'reference keyword' => ['R'];
        yield 'digits then letters' => ['12abc'];
    }

    public function testTokenizesIndirectObjectHeader(): void
    {
        $lexer = new Lexer("1 0 obj\n<< /Type /Page >>\nendobj");
        $types = [];
        $lexemes = [];

        do {
            $token = $lexer->next();
            $types[] = $token->type;
            $lexemes[] = $token->lexeme;
        } while ($token->type !== Type::Eof);

        self::assertSame(
            [Type::Num, Type::Num, Type::Keyword, Type::DictOpen, Type::Name, Type::Name, Type::DictClose, Type::Keyword, Type::Eof],
            $types,
        );
        self::assertSame(['1', '0', 'obj', '<<', 'Type', 'Page', '>>', 'endobj', ''], $lexemes);
    }

    public function testScansName(): void
    {
        $token = new Lexer('/Type ')->next();

        self::assertSame(Type::Name, $token->type);
        self::assertSame('Type', $token->lexeme);
        self::assertSame(0, $token->offset);
    }

    public function testNameStopsAtDelimiter(): void
    {
        $lexer = new Lexer('/Type/Page>>');

        self::assertSame('Type', $lexer->next()->lexeme);
        self::assertSame('Page', $lexer->next()->lexeme);
        self::assertSame(Type::DictClose, $lexer->next()->type);
    }

    public function testBareSlashIsEmptyName(): void
    {
        $token = new Lexer('/ ')->next();

        self::assertSame(Type::Name, $token->type);
        self::assertSame('', $token->lexeme);
    }

    public function testNameDecodesHexEscapes(): void
    {
        self::assertSame('A B#', new Lexer('/A#20B#23')->next()->lexeme);
    }

    public function testNameKeepsHashWithoutTwoHexDigits(): void
    {
        self::assertSame('a#b#1', new Lexer('/a#b#1')->next()->lexeme);
    }

    public function testScansLiteralString(): void
    {
        $token = new Lexer('(Hello) ')->next();

        self::assertSame(Type::Str, $token->type);
        self::assertSame('Hello', $token->lexeme);
        self::assertSame(0, $token->offset);
    }

    public function testLiteralStringKeepsBalancedNestedParentheses(): void
    {
        $lexer = new Lexer('(a(b)c)]');

        self::assertSame('a(b)c', $lexer->next()->lexeme);
        self::assertSame(Type::ArrClose, $lexer->next()->type);
    }

    public function testLiteralStringMayContainDelimitersAndWhitespace(): void
    {
        self::assertSame('a <</b> %c', new Lexer('(a <</b> %c)')->next()->lexeme);
    }

    #[DataProvider('stringEscapeProvider')]
    public function testLiteralStringDecodesEscapes(string $input, string $expected): void
    {
        self::assertSame($expected, new Lexer($input)->next()->lexeme);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function stringEscapeProvider(): iterable
    {
        yield 'named escapes' => ['(\\n\\r\\t\\b\\f)', "\n\r\t\x08\x0C"];
        yield 'escaped parentheses' => ['(\\(a\\))', '(a)'];
        yield 'escaped backslash' => ['(a\\\\b)', 'a\\b'];
        yield 'unbalanced escaped paren does not close' => ['(a\\)b)', 'a)b'];
        yield 'three octal digits' => ['(\\101)', 'A'];
        yield 'two octal digits' => ['(\\41)', '!'];
        yield 'one octal digit followed by non digit' => ['(\\7x)', "\x07x"];
        yield 'octal stops after three digits' => ['(\\0053)', "\x053"];
        yield 'octal overflow wraps to low byte' => ['(\\777)', "\xFF"];
        yield 'backslash lf continuation' => ["(a\\\nb)", 'ab'];
        yield 'backslash cr continuation' => ["(a\\\rb)", 'ab'];
        yield 'backslash crlf continuation' => ["(a\\\r\nb)", 'ab'];
        yield 'unknown escape drops backslash' => ['(\\q)', 'q'];
    }

    #[DataProvider('stringEolProvider')]
    public function testLiteralStringNormalizesUnescapedEndOfLine(string $input): void
    {
        self::assertSame("a\nb", new Lexer($input)->next()->lexeme);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function stringEolProvider(): iterable
    {
        yield 'lf' => ["(a\nb)"];
        yield 'cr' => ["(a\rb)"];
        yield 'crlf' => ["(a\r\nb)"];
    }

    public function testLiteralStringPreservesRawBytes(): void
    {
        self::assertSame("\xFE\xFF\x00", new Lexer("(\xFE\xFF\x00)")->next()->lexeme);
    }

    public function testUnterminatedLiteralStringThrows(): void
    {
        $this->expectException(LexerException::class);
        $this->expectExceptionMessage('offset 3');

        $lexer = new Lexer('ab (cd');
        $lexer->next();
        $lexer->next();
    }

    public function testUnterminatedLiteralStringEndingInBackslashThrows(): void
    {
        $this->expectException(LexerException::class);

        new Lexer('(ab\\')->next();
    }

    public function testScansHexString(): void
    {
        $token = new Lexer('<48656C6C6F> ')->next();

        self::assertSame(Type::HexStr, $token->type);
        self::assertSame('Hello', $token->lexeme);
        self::assertSame(0, $token->offset);
    }

    public function testHexStringAcceptsLowercaseAndWhitespace(): void
    {
        self::assertSame('Hi', new Lexer("<48\n 6 9\t>")->next()->lexeme);
    }

    public function testHexStringPadsOddDigitCountWithZero(): void
    {
        self::assertSame("\x41\x20", new Lexer('<412>')->next()->lexeme);
    }

    public function testEmptyHexString(): void
    {
        $lexer = new Lexer('<>]');

        self::assertSame('', $lexer->next()->lexeme);
        self::assertSame(Type::ArrClose, $lexer->next()->type);
    }

    public function testHexStringRejectsNonHexByte(): void
    {
        $this->expectException(LexerException::class);
        $this->expectExceptionMessage('offset 2');

        new Lexer('<4G>')->next();
    }

    public function testUnterminatedHexStringThrows(): void
    {
        $this->expectException(LexerException::class);
        $this->expectExceptionMessage('offset 0');

        new Lexer('<41')->next();
    }

    #[DataProvider('unexpectedByteProvider')]
    public function testUnexpectedDelimiterThrows(string $input): void
    {
        $this->expectException(LexerException::class);
        $this->expectExceptionMessage('offset 2');

        $lexer = new Lexer($input);
        $lexer->next();
        $lexer->next();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unexpectedByteProvider(): iterable
    {
        yield 'closing paren' => ['x )'];
        yield 'lone greater than' => ['x >'];
    }
}
