<?php

declare(strict_types=1);

namespace Slurp\Tokens;

enum Type
{
    case Num;
    case Str;
    case HexStr;
    case Name;
    case DictOpen;
    case DictClose;
    case ArrOpen;
    case ArrClose;
    case BraceOpen;
    case BraceClose;
    case Keyword;
    case Eof;
}
