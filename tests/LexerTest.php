<?php

namespace Tests;

use JsonParser\Lexer;
use JsonParser\TokenType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Lexer::class)]
class LexerTest extends TestCase
{
    #[Test]
    #[DataProvider('primitiveTokenProvider')]
    public function itCanTokenizePrimitiveTypes(
        string $jsonString,
        TokenType $expectedType,
        string $expectedValue,
    ): void {
        $lexer = new Lexer($jsonString);
        $tokens = $lexer->tokenize();

        $this->assertCount(1, $tokens, "Failed to tokenize single primitive: {$jsonString}");
        $this->assertSame($expectedType, $tokens[0]->type, "Token type incorrect for: {$jsonString}");

        $this->assertEquals(
            $expectedValue,
            $tokens[0]->value,
            "Token value does not match expected output for: {$jsonString}",
        );
    }

    #[Test]
    #[DataProvider('faultyTokenProvider')]
    public function itThrowsAnErrorOnInvalidTokens(string $jsonString): void
    {
        $this->expectException(\Exception::class);

        $lexer = new Lexer($jsonString);
        $tokens = $lexer->tokenize();
    }

    /**
     * @return array<array<string, TokenType, string>>
     */
    public static function primitiveTokenProvider(): array
    {
        return [
            ['null',                TokenType::Null,   'null'],
            ['true',                TokenType::True,   'true'],
            ['false',               TokenType::False,  'false'],
            ['""',                  TokenType::String, ''],
            ['"a"',                 TokenType::String, 'a'],
            ['"hello world"',       TokenType::String, 'hello world'],
            ['"A B C"',             TokenType::String, 'A B C'],
            ['"\\""',               TokenType::String, '"'],
            ['"\\\\"',              TokenType::String, "\\"],
            ['"\\/"',               TokenType::String, '/'],
            ['"Line\\nBreak"',      TokenType::String, "Line\nBreak"],
            ['"Carriage\\rReturn"', TokenType::String, "Carriage\rReturn"],
            ['"Tab\\tSpace"',       TokenType::String, "Tab\tSpace"],
            ['"Feed\\fChar"',       TokenType::String, "Feed\fChar"],
            ['"Back\\bSpace"',      TokenType::String, "Back\bSpace"],
            ['"\u0041"',            TokenType::String, 'A'],
            ['"\u20ac"',            TokenType::String, '€'],
            ['"\u010d\u0107"',      TokenType::String, 'čć'],
            ['"A\u0020B"',          TokenType::String, 'A B'],
        ];
    }

    /**
     * @return string[]
     */
    public static function faultyTokenProvider(): array
    {
        return [
            ['nul'],
            ['truee'],
            ['fal'],
            ['False'],
            ['t'],
            ['"Unclosed'],
            ['"Unclosed\\'],
            ['"Invalid\\xEscape"'],
            ['"Invalid\\gEscape"'],
            ['"\u004"'],
            ['"\u004z"'],
            ['"\u4"'],
            ['"\uzzzzz"'],
        ];
    }
}
