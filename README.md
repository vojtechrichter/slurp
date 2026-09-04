# Slurp

Slurp is a very lightweight (thus offers limited functionality) PDF parser library for PHP.

The primary goal of this project is learning experience, and discovery of internals of the Portable Document Format.

## Current state

Slurp currently reads a PDF file, detects its version from the header, and tokenizes the file body. There is no object parser yet, so the output is a flat token stream.

The lexer recognizes every token type defined by the PDF syntax:

- whitespace and comments are skipped, `%%EOF` is reported as an end-of-file token
- numbers (`42`, `-3`, `3.14`, `-.002`) and keywords (`obj`, `R`, `true`, `stream`, ...)
- names with `#xx` escapes decoded (`/Type`, `/A#20B`)
- literal strings with nesting, backslash escapes, octal codes, and line continuation (`(Hello)`)
- hex strings (`<48656C6C6F>`)
- delimiters `[` `]` `{` `}` `<<` `>>`

String and name tokens carry the decoded bytes, delimiters and keywords carry the raw text. Every token knows its byte offset in the file. Malformed input, such as an unterminated string, raises `Slurp\LexerException` with the offset.

Stream bodies are not handled yet. The lexer stops making sense after a `stream` keyword, and on a file with compressed streams it fails once it hits binary data. Reading bodies by their `/Length` is the job of the upcoming object parser.

## Try it

Dump the token stream of a sample file:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$pdf = \Slurp\PdfResource::from('samples/1.pdf');
$lexer = new \Slurp\Lexer($pdf->getRawContent());

printf("%8s  %-10s  %s\n", 'OFFSET', 'TYPE', 'LEXEME');

do {
    $token = $lexer->next();
    printf(
        "%8d  %-10s  %s\n",
        $token->offset,
        $token->type->name,
        addcslashes($token->lexeme, "\x00..\x1F\x7F..\xFF"),
    );
} while ($token->type !== \Slurp\Tokens\Type::Eof);
```

Output:

```
  OFFSET  TYPE        LEXEME
       9  Num         1
      11  Num         0
      13  Keyword     obj
      17  DictOpen    <<
      20  Name        Type
      26  Name        Catalog
      35  Name        Pages
      42  Num         2
      44  Num         0
      46  Keyword     R
      48  DictClose   >>
      51  Keyword     endobj
     ...
```

`samples/1.pdf` has uncompressed streams and tokenizes to the end. `samples/2.pdf` has a Flate-compressed stream and currently fails at its first stream body, which is expected.

## Development

```bash
composer test
composer phpstan
```
