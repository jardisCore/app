<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Request;

use JardisCore\App\Exception\InvalidJsonBody;
use JardisCore\App\Handler\Request\ParseJsonBody;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Integration tests for ParseJsonBody - the one JSON parser (E7). Requests
 * are built with the real nyholm ServerRequest so the stream behaviour
 * (rewind/getContents/seek) is exercised for real, not against a fake.
 */
final class ParseJsonBodyTest extends TestCase
{
    private function requestWithBody(string $raw): ServerRequestInterface
    {
        $factory = new Psr17Factory();

        return $factory
            ->createServerRequest('POST', '/orders')
            ->withBody($factory->createStream($raw));
    }

    public function testParsesValidJsonAndPreservesByteIdenticalBodyAfterwards(): void
    {
        $raw = "{\n  \"name\": \"José äöü\",\n  \"note\": \"  padded  \"\n}";
        $request = $this->requestWithBody($raw);
        $parser = new ParseJsonBody();

        $decoded = $parser($request);

        $this->assertSame('José äöü', $decoded['name']);
        $this->assertSame('  padded  ', $decoded['note']);

        // Byte-identical raw data must still be retrievable after parsing (webhook-HMAC case).
        $request->getBody()->rewind();
        $this->assertSame($raw, $request->getBody()->getContents());
    }

    public function testThrowsInvalidJsonBodyOnSyntaxError(): void
    {
        $request = $this->requestWithBody('{"broken":');
        $parser = new ParseJsonBody();

        $this->expectException(InvalidJsonBody::class);

        $parser($request);
    }

    public function testThrowsInvalidJsonBodyOnEmptyBody(): void
    {
        $request = $this->requestWithBody('');
        $parser = new ParseJsonBody();

        $this->expectException(InvalidJsonBody::class);

        $parser($request);
    }

    public function testThrowsInvalidJsonBodyWhenDecodedValueIsNotAnArray(): void
    {
        $request = $this->requestWithBody('"just a string"');
        $parser = new ParseJsonBody();

        $this->expectException(InvalidJsonBody::class);

        $parser($request);
    }

    public function testGetBodyCanBeReadTwiceInFullAfterParsing(): void
    {
        $raw = '{"a":1}';
        $request = $this->requestWithBody($raw);
        $parser = new ParseJsonBody();

        $parser($request);

        $request->getBody()->rewind();
        $firstRead = $request->getBody()->getContents();
        $request->getBody()->rewind();
        $secondRead = $request->getBody()->getContents();

        $this->assertSame($raw, $firstRead);
        $this->assertSame($raw, $secondRead);
    }

    public function testParsingAnAlreadyConsumedStreamStillWorksViaRewind(): void
    {
        $raw = '{"already":"read"}';
        $request = $this->requestWithBody($raw);

        // Simulate a caller who already fully read the stream before parsing.
        $request->getBody()->getContents();

        $parser = new ParseJsonBody();
        $decoded = $parser($request);

        $this->assertSame(['already' => 'read'], $decoded);
    }
}
