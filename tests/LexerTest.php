<?php

namespace Tests;

use JsonParser\Lexer;
use JsonParser\TokenType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Lexer::class)]
class LexerTest extends TestCase
{
    #[Test]
    public function itThrowsAnErrorOnAnEmptyString(): void
    {
        $this->expectException(\Exception::class);

        $lexer = new Lexer("");
    }

    #[Test]
    public function itCanTokenizeNull(): void
    {
        $lexer = new Lexer("null");
        $tokens = $lexer->tokenize();

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::Null, $tokens[0]->type);
        $this->assertEquals("null", $tokens[0]->value);
    }
}
