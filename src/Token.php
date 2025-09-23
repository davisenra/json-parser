<?php

declare(strict_types=1);

namespace JsonParser;

class Token
{
    public function __construct(
        public readonly TokenType $type,
        public readonly string $value,
    ) {}
}
