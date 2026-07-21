<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Request;

use JardisCore\App\Handler\Request\CreateServerRequest;
use JardisCore\App\Handler\Request\ParseJsonBody;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for CreateServerRequest against the real nyholm PSR-17
 * factories (E12: the handler only ever sees the PSR interfaces; nyholm is
 * injected explicitly here as the test's choice of implementation).
 */
final class CreateServerRequestTest extends TestCase
{
    private function createHandler(): CreateServerRequest
    {
        $factory = new Psr17Factory();

        return new CreateServerRequest($factory, $factory, $factory, $factory);
    }

    public function testBuildsRequestFromInjectedGlobalsIncludingMethodUriAndHeaders(): void
    {
        $handler = $this->createHandler();

        $request = $handler(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/orders?foo=bar',
                'HTTP_HOST' => 'example.test',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
            ],
            headers: ['content-type' => 'application/json', 'x-request-id' => 'abc-123'],
            cookies: [],
            query: ['foo' => 'bar'],
            files: [],
            body: '{"a":1}',
        );

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/orders', $request->getUri()->getPath());
        $this->assertSame('example.test', $request->getUri()->getHost());
        $this->assertSame('application/json', $request->getHeaderLine('content-type'));
        $this->assertSame('abc-123', $request->getHeaderLine('x-request-id'));
        $this->assertSame(['foo' => 'bar'], $request->getQueryParams());
    }

    public function testBodyStreamIsSeekableAndReadable(): void
    {
        $handler = $this->createHandler();

        $request = $handler(
            server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/health'],
            headers: [],
            body: 'raw-payload',
        );

        $body = $request->getBody();

        $this->assertTrue($body->isSeekable());
        $body->rewind();
        $this->assertSame('raw-payload', $body->getContents());
    }

    public function testDoubleParseOfBuiltRequestIsIdempotent(): void
    {
        $handler = $this->createHandler();
        $parser = new ParseJsonBody();

        $request = $handler(
            server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/orders'],
            headers: ['content-type' => 'application/json'],
            body: '{"key":"value"}',
        );

        $first = $parser($request);
        $second = $parser($request);

        $this->assertSame(['key' => 'value'], $first);
        $this->assertSame(['key' => 'value'], $second);
        $this->assertSame('{"key":"value"}', (string) $request->getBody());
    }

    public function testAppliesDefaultsForHeadersCookiesQueryAndFilesWhenOmitted(): void
    {
        $handler = $this->createHandler();

        // headers/cookies/query/files intentionally omitted - the CLI SAPI has no
        // getallheaders(), so this exercises the ServerRequestCreator::getHeadersFromServer
        // fallback plus the $_COOKIE/$_GET/$_FILES defaults.
        $request = $handler(
            server: [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/health',
                'HTTP_X_TEST' => 'custom-value',
            ],
            body: 'ok',
        );

        $this->assertSame('custom-value', $request->getHeaderLine('x-test'));
        $this->assertSame([], $request->getCookieParams());
        $this->assertSame([], $request->getQueryParams());
        $this->assertSame([], $request->getUploadedFiles());
    }
}
