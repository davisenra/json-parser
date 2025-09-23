<?php

declare(strict_types=1);

namespace JsonParser;

class JsonParser
{
    public function parse(string $jsonString): mixed
    {
        $tokens = new Lexer($jsonString)->tokenize();

        return null;
    }
}
