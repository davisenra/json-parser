<?php

declare(strict_types=1);

namespace JsonParser;

enum TokenType
{
    case Colon;
    case Comma;
    case OpenBrace;
    case CloseBrace;
    case OpenBracket;
    case CloseBracket;
    case String;
    case Number;
    case True;
    case False;
    case Null;
}
