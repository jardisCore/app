<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Response;

use JardisCore\App\Handler\Response\BuildErrorResponse;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BuildErrorResponse (F9): the canonical boundary-error builder
 * for 400/404/405/500 - and, via MapDomainResponse, every other envelope
 * response the package produces (P4/P5 consolidation, single builder).
 *
 * Covers: 404 in the envelope schema; 405 in the envelope schema WITH the
 * RFC-7231 Allow header (multiple methods, `, `-format, string assertion);
 * E2b object-coercion.
 */
final class BuildErrorResponseTest extends TestCase
{
    private function build(): BuildErrorResponse
    {
        $factory = new Psr17Factory();

        return new BuildErrorResponse($factory, $factory);
    }

    public function testBuildsA404EnvelopeResponse(): void
    {
        $build = $this->build();

        $response = $build(404);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"status":404,"data":{},"errors":{},"meta":{}}', (string) $response->getBody());
        $this->assertFalse($response->hasHeader('Allow'));
    }

    public function testBuildsA405EnvelopeResponseWithAllowHeaderListingMultipleMethods(): void
    {
        $build = $this->build();

        $response = $build(405, allowedMethods: ['GET', 'POST', 'PATCH']);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, POST, PATCH', $response->getHeaderLine('Allow'));
        $this->assertSame('{"status":405,"data":{},"errors":{},"meta":{}}', (string) $response->getBody());
    }

    public function testAllowHeaderIsAbsentWhenAllowedMethodsIsEmpty(): void
    {
        $build = $this->build();

        $response = $build(405);

        $this->assertFalse($response->hasHeader('Allow'));
    }

    public function testBuildsA400EnvelopeResponseWithErrors(): void
    {
        $build = $this->build();

        $response = $build(400, errors: ['message' => 'bad request']);

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(['message' => 'bad request'], $body['errors']);
        $this->assertSame([], $body['data']);
    }

    public function testBuildsA500GenericEnvelopeResponse(): void
    {
        $build = $this->build();

        $response = $build(500);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('{"status":500,"data":{},"errors":{},"meta":{}}', (string) $response->getBody());
    }

    public function testNonEmptyDataErrorsAndMetaAreSerializedAsIs(): void
    {
        $build = $this->build();

        $response = $build(
            409,
            data: ['id' => '42'],
            errors: ['field' => 'conflict'],
            meta: ['duration' => 3],
        );

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(['id' => '42'], $body['data']);
        $this->assertSame(['field' => 'conflict'], $body['errors']);
        $this->assertSame(['duration' => 3], $body['meta']);
    }
}
