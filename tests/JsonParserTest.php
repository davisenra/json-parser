<?php

namespace Tests;

use JsonParser\JsonParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonParser::class)]
class JsonParserTest extends TestCase
{
    #[Test]
    public function itParsesAJsonObject(): void
    {
        $parser = new JsonParser();
        $output = $parser->parse('{"hello":"world"}');

        $this->assertIsObject($output);
        $this->assertEquals("world", $output->hello);
    }
}
