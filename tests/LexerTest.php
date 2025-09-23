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

    #[Test]
    public function itCanTokenizeTrue(): void
    {
        $lexer = new Lexer("true");
        $tokens = $lexer->tokenize();

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::True, $tokens[0]->type);
        $this->assertEquals("true", $tokens[0]->value);
    }

    #[Test]
    public function itCanTokenizeFalse(): void
    {
        $lexer = new Lexer("false");
        $tokens = $lexer->tokenize();

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::False, $tokens[0]->type);
        $this->assertEquals("false", $tokens[0]->value);
    }

    #[Test]
    public function itCanTokenizeAnArrayOfBooleans(): void
    {
        $lexer = new Lexer("[true, false]");
        $tokens = $lexer->tokenize();

        $this->assertCount(5, $tokens);
        $this->assertSame(TokenType::OpenBracket, $tokens[0]->type);
        $this->assertSame(TokenType::True, $tokens[1]->type);
        $this->assertSame(TokenType::Comma, $tokens[2]->type);
        $this->assertSame(TokenType::False, $tokens[3]->type);
        $this->assertSame(TokenType::CloseBracket, $tokens[4]->type);
    }

    #[Test]
    public function itCanTokeninzeAnEmptyObject(): void
    {
        $lexer = new Lexer("{}");
        $tokens = $lexer->tokenize();

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::OpenBrace, $tokens[0]->type);
        $this->assertSame(TokenType::CloseBrace, $tokens[1]->type);
    }
}
