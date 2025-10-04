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
            throw new \Exception("Invalid JSON string provided, length is zero");
        }
    }

    /**
     * @return Token[]
     */
    public function tokenize(): array
    {
        do {
            $jToken = $this->jsonString[$this->needle] ?? null;

            if ($jToken === null) {
                break;
            }

            if ($jToken === " ") {
                $this->advance();
                continue;
            }

            match ($jToken) {
                '"' => $this->lexString(),
                "n" => $this->lexNull(),
                "t" => $this->lexTrue(),
                "f" => $this->lexFalse(),
                "{", "}", ":", ",", "[", "]" => $this->lexStructuralToken(),
                "-", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" => $this->lexNumber(),
                default => $this->invalidToken($jToken, $this->needle),
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

    private function lexString(): void
    {
        // Skip first quote
        $this->advance();

        $tokenValue = "";
        $stringIndex = 0;

        while (true) {
            $currentChar = $this->jsonString[$this->needle + $stringIndex] ?? null;
            $nextChar = $this->jsonString[$this->needle + $stringIndex + 1] ?? null;

            if ($currentChar === '"') {
                // Empty string
                if ($nextChar === '"') {
                    $this->pushToken(TokenType::String, $tokenValue);
                    $this->advance($stringIndex + 2);
                    return;
                }

                // String ended
                if ($nextChar === null) {
                    $this->pushToken(TokenType::String, $tokenValue);
                    $this->advance($stringIndex + 2);
                    return;
                }
            }

            if ($currentChar === null) {
                // We've reached the end of the file and a closing quote was not found
                if ($this->needle + $stringIndex === $this->jsonLength) {
                    throw new \Exception("Unclosed string at position: {$this->needle}");
                }

                $this->pushToken(TokenType::String, $tokenValue);
                $this->advance($stringIndex + 1);
                return;
            }

            // Special character
            if ($currentChar === "\\") {
                $specialChar = match ($nextChar) {
                    // Oh my God, doing JSON/PHP string matching is WEIRD
                    '"' => '"',
                    "\\" => '\\',
                    '/' => '/',
                    'n' => "\n",
                    't' => "\t",
                    'f' => "\f",
                    'b' => "\b",
                    'u' => 'u',
                    default => $this->invalidToken($nextChar, $this->needle + $stringIndex),
                };

                $isUnicode = $specialChar === 'u';

                if ($isUnicode) {
                    $tokenValue = $this->lexUnicode($stringIndex);
                    $stringIndex += 6;
                    continue;
                }

                // Attends this case: "\\\\"
                if (mb_substr($tokenValue, -1) === "\\") {
                    $stringIndex++;
                    continue;
                }

                $tokenValue .= $specialChar;
                $stringIndex += 2;
                continue;
            }

            // First character
            if ($currentChar === '"' && $tokenValue === "") {
                $stringIndex++;
                continue;
            }

            $tokenValue .= $currentChar;
            $stringIndex++;
        }
    }

    private function lexNumber(): void
    {
        $tokenValue = $this->jsonString[$this->needle];
        $stringIndex = 1;

        $hasLeadingZero = $tokenValue === "0";
        $hasDecimal = false;
        $hasExponent = false;

        do {
            $nextChar = $this->jsonString[$this->needle + $stringIndex] ?? null;

            if ($nextChar === null) {
                break;
            }

            if ($hasLeadingZero && $stringIndex === 1 && $nextChar !== ".") {
                // leading zeroes are not allowed on integers
                $this->invalidToken($tokenValue, $this->needle + $stringIndex);
            }

            $keepConsuming =
                $stringIndex < $this->jsonLength && $this->isValidNumberChar($nextChar);

            $tokenValue .= $nextChar;
            $stringIndex++;
        } while ($keepConsuming);

        $this->pushToken(TokenType::Number, $tokenValue);
        $this->advance($stringIndex + 1);
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

        $this->invalidToken($this->jsonString[$this->needle], $this->needle);
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

        $this->invalidToken($this->jsonString[$this->needle], $this->needle);
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

        $this->invalidToken($this->jsonString[$this->needle], $this->needle);
    }

    private function invalidToken(string $token, int $position): void
    {
        throw new \Exception("Invalid token found at position {$position}: '{$token}'");
    }

    private function isValidNumberChar(string $char): bool
    {
        return match ($char) {
            "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ".", "-", "+", "e", "E" => true,
            default => false,
        };
    }

    private function lexUnicode(int $currentStringIndex): string
    {
        $hexadecimal = [
            $this->jsonString[$this->needle + $currentStringIndex + 2],
            $this->jsonString[$this->needle + $currentStringIndex + 3],
            $this->jsonString[$this->needle + $currentStringIndex + 4],
            $this->jsonString[$this->needle + $currentStringIndex + 5],
        ];

        $hexadecimal = array_filter($hexadecimal, fn ($string) => $string !== null);
        $hexadecimal = array_filter($hexadecimal, $this->isValidHexadecimalDigit(...));
        $hexadecimal = join($hexadecimal);

        if (mb_strlen($hexadecimal) !== 4) {
            $position = $this->needle . $currentStringIndex;
            throw new \Exception("Invalid token found at position: {$position}");
        }

        $hexadecimal = hexdec($hexadecimal);
        // Convert the decimal code point into a UTF-8 character
        // PHP strings folks...
        $hexadecimal = html_entity_decode('&#' . $hexadecimal . ';', ENT_QUOTES, 'UTF-8');

        return $hexadecimal;
    }

    private function isValidHexadecimalDigit(string $char): bool
    {
        return ctype_xdigit($char);
    }
}
