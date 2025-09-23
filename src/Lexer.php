<?php

declare(strict_types=1);

namespace JsonParser;

class Lexer
{
    private int $needle = 0;
    private int $jsonLength;

    /** @var Token[] $tokens */
    private array $tokens = [];

    /** @var array{string, TokenType} $structuralTokenMap */
    private array $structuralTokenMap = [
        "{" => TokenType::OpenBrace,
        "}" => TokenType::CloseBrace,
        ":" => TokenType::Colon,
        "," => TokenType::Comma,
        "[" => TokenType::OpenBracket,
        "]" => TokenType::CloseBracket,
    ];

    public function __construct(private readonly string $jsonString)
    {
        $this->jsonLength = mb_strlen($jsonString);

        if ($this->jsonLength === 0) {
            throw new \Exception(
                "Invalid JSON string provided, length is zero",
            );
        }
    }

    /**
     * @return Token[]
     */
    public function tokenize(): array
    {
        do {
            $jToken = $this->jsonString[$this->needle];

            if ($jToken === " ") {
                $this->advance();
                continue;
            }

            match ($jToken) {
                "n" => $this->lexNull(),
                "t" => $this->lexTrue(),
                "f" => $this->lexFalse(),
                "{", "}", ":", ",", "[", "]" => $this->lexStructuralToken(),
                default => throw new \Exception(
                    "Unhandled match condition while tokenizing",
                ),
            };
        } while ($this->needle < $this->jsonLength);

        return $this->tokens;
    }

    private function pushToken(TokenType $type, string $value): void
    {
        $this->tokens[] = new Token($type, $value);
    }

    private function advance(int $amount = 1): void
    {
        $this->needle += $amount;
    }

    private function lexStructuralToken(): void
    {
        $jToken = $this->jsonString[$this->needle];

        $this->pushToken($this->structuralTokenMap[$jToken], $jToken);
        $this->advance();
    }

    private function lexNull(): void
    {
        $isValidToken =
            $this->jsonString[$this->needle + 1] === "u" &&
            $this->jsonString[$this->needle + 2] === "l" &&
            $this->jsonString[$this->needle + 3] === "l";

        if ($isValidToken) {
            $this->pushToken(TokenType::Null, "null");
            $this->advance(4);
            return;
        }

        // TODO: throw an error here
    }

    private function lexTrue(): void
    {
        $isValidToken =
            $this->jsonString[$this->needle + 1] === "r" &&
            $this->jsonString[$this->needle + 2] === "u" &&
            $this->jsonString[$this->needle + 3] === "e";

        if ($isValidToken) {
            $this->pushToken(TokenType::True, "true");
            $this->advance(4);
            return;
        }

        // TODO: throw an error here
    }

    private function lexFalse(): void
    {
        $isValidToken =
            $this->jsonString[$this->needle + 1] === "a" &&
            $this->jsonString[$this->needle + 2] === "l" &&
            $this->jsonString[$this->needle + 3] === "s" &&
            $this->jsonString[$this->needle + 4] === "e";

        if ($isValidToken) {
            $this->pushToken(TokenType::False, "false");
            $this->advance(5);
            return;
        }

        // TODO: throw an error here
    }
}
