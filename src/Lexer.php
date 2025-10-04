<?php

declare(strict_types=1);

namespace JsonParser;

final class Lexer
{
    private int $needle = 0;
    private int $jsonLength;

    /** @var Token[] $tokens */
    private array $tokens = [];

    /** @var array<string, TokenType> $structuralTokenMap */
    private array $structuralTokenMap = [
        '{' => TokenType::OpenBrace,
        '}' => TokenType::CloseBrace,
        ':' => TokenType::Colon,
        ',' => TokenType::Comma,
        '[' => TokenType::OpenBracket,
        ']' => TokenType::CloseBracket,
    ];

    public function __construct(
        private readonly string $jsonString,
    ) {
        $this->jsonLength = mb_strlen($jsonString);

        if ($this->jsonLength === 0) {
            throw new \Exception('Invalid JSON string provided, length is zero');
        }
    }

    /**
     * @return Token[]
     */
    public function tokenize(): array
    {
        while ($this->needle < $this->jsonLength) {
            $jToken = $this->jsonString[$this->needle];

            if ($jToken === ' ') {
                $this->advance();
                continue;
            }

            match ($jToken) {
                '"' => $this->lexString(),
                'n' => $this->lexLiteral('null', TokenType::Null, 4),
                't' => $this->lexLiteral('true', TokenType::True, 4),
                'f' => $this->lexLiteral('false', TokenType::False, 5),
                '-', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' => $this->lexNumber(),
                '{', '}', ':', ',', '[', ']' => $this->lexStructuralToken(),
                default => $this->invalidToken($jToken, $this->needle),
            };
        }

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

    private function invalidToken(string $token, int $position): void
    {
        throw new \Exception("Invalid token found at position {$position}: '{$token}'");
    }

    private function lexLiteral(string $expectedValue, TokenType $type, int $length): void
    {
        if (($this->needle + $length) > $this->jsonLength) {
            $this->invalidToken($this->jsonString[$this->needle], $this->needle);
        }

        $actualValue = mb_substr($this->jsonString, $this->needle, $length);

        if ($actualValue === $expectedValue) {
            $this->pushToken($type, $expectedValue);
            $this->advance($length);
            return;
        }

        $this->invalidToken($this->jsonString[$this->needle], $this->needle);
    }

    private function lexStructuralToken(): void
    {
        $jToken = $this->jsonString[$this->needle];

        $this->pushToken($this->structuralTokenMap[$jToken], $jToken);
        $this->advance();
    }

    private function lexString(): void
    {
        $this->advance(); // Skip the initial quote '"'

        $tokenValue = '';
        $stringIndex = 0;

        while (true) {
            $currentChar = $this->jsonString[$this->needle + $stringIndex] ?? null;

            if ($currentChar === null) {
                throw new \Exception("Unclosed string at position: {$this->needle}");
            }

            // 1. Closing Quote Found
            if ($currentChar === '"') {
                $this->pushToken(TokenType::String, $tokenValue);
                $this->advance($stringIndex + 1); // Advance past content + closing quote
                return;
            }

            // 2. Escape Sequence Found
            if ($currentChar === "\\") {
                $stringIndex++; // Move consumption index past the backslash
                $nextChar = $this->jsonString[$this->needle + $stringIndex] ?? null;

                if ($nextChar === null) {
                    throw new \Exception("Unclosed string at position: {$this->needle}");
                }

                $isUnicode = $nextChar === 'u';

                $escapedChar = match ($nextChar) {
                    '"' => '"',
                    "\\" => '\\',
                    '/' => '/',
                    'b' => "\b",
                    'f' => "\f",
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'u' => $this->lexUnicode($stringIndex),
                    default => $this->invalidToken($nextChar, $this->needle + $stringIndex),
                };

                $tokenValue .= $escapedChar;

                // For simple escapes, advance 1 more (the escaped char)
                if (!$isUnicode) {
                    $stringIndex++;
                }

                // For Unicode, lexUnicode handles advancing $stringIndex by 4 more chars
                continue;
            }

            // 3. Normal Character
            $tokenValue .= $currentChar;
            $stringIndex++;
        }
    }

    private function lexUnicode(int &$stringIndex): string
    {
        $start = $this->needle + $stringIndex + 1;
        $hexValue = '';

        for ($i = 0; $i < 4; $i++) {
            $char = $this->jsonString[$start + $i] ?? null;

            if ($char === null || !ctype_xdigit($char)) {
                throw new \Exception("Invalid Unicode escape sequence at position: {$start}");
            }
            $hexValue .= $char;
        }

        $decimalCodePoint = hexdec($hexValue);
        $utf8Char = html_entity_decode('&#' . $decimalCodePoint . ';', ENT_QUOTES, 'UTF-8');

        // Advance the string index by 4 more positions (for the XXXX)
        $stringIndex += 5;

        return $utf8Char;
    }

    private function isValidHexadecimalDigit(string $char): bool
    {
        return ctype_xdigit($char);
    }

    private function lexNumber(): void
    {
        $tokenValue = $this->jsonString[$this->needle];
        $stringIndex = 1;

        $hasLeadingZero = $tokenValue === '0';
        $hasDecimal = false;
        $hasExponent = false;

        do {
            $nextChar = $this->jsonString[$this->needle + $stringIndex] ?? null;

            if ($nextChar === null) {
                break;
            }

            if ($hasLeadingZero && $stringIndex === 1 && $nextChar !== '.') {
                $this->invalidToken($tokenValue, $this->needle + $stringIndex);
            }

            $keepConsuming = $stringIndex < $this->jsonLength && $this->isValidNumberChar($nextChar);

            $tokenValue .= $nextChar;
            $stringIndex++;
        } while ($keepConsuming);

        $this->pushToken(TokenType::Number, $tokenValue);
        $this->advance($stringIndex);
    }

    private function isValidNumberChar(string $char): bool
    {
        return match ($char) {
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '-', '+', 'e', 'E' => true,
            default => false,
        };
    }
}
