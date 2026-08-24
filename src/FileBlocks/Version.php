<?php

declare(strict_types=1);

namespace Slurp\FileBlocks;

enum Version: string
{
    case V10 = '1.0';
    case V11 = '1.1';
    case V12 = '1.2';
    case V13 = '1.3';
    case V14 = '1.4';
    case V15 = '1.5';
    case V16 = '1.6';
    case V17 = '1.7';
}
