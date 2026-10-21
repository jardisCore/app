<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Response;

use JardisCore\App\Handler\Response\AnswerUnsupportedRequest;
use JardisCore\App\Handler\Response\MapDomainResponse;
use JardisSupport\Response\Data\Response\DomainResponse;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for AnswerUnsupportedRequest - the 500 of a route whose
 * request the generated adapter cannot build. The envelope is checked through
 * the real MapDomainResponse, byte for byte.
 */
final class AnswerUnsupportedRequestTest extends TestCase
{
    public function testAnswersInternalErrorWithTheNamedPropertiesAndNoData(): void
    {
        $answer = (new AnswerUnsupportedRequest())(['items', 'meta']);

        $this->assertInstanceOf(DomainResponse::class, $answer);
        $this->assertSame(500, $answer->getStatus());
        $this->assertSame([], $answer->getData());
        $this->assertSame(
            ['routes' => ['Request property not buildable by the generated adapter: items, meta']],
            $answer->getErrors(),
        );
        $this->assertSame([], $answer->getMetadata());
    }

    public function testEnvelopeOnTheWire(): void
    {
        $factory = new Psr17Factory();
        $response = (new MapDomainResponse($factory, $factory))((new AnswerUnsupportedRequest())(['lines']));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            '{"status":500,"data":{},"errors":{"routes":["Request property not buildable by the generated adapter: lines"]},"meta":{}}',
            (string) $response->getBody(),
        );
    }

    public function testNoPropertyYieldsAnEmptyList(): void
    {
        $answer = (new AnswerUnsupportedRequest())([]);

        $this->assertSame(
            ['routes' => ['Request property not buildable by the generated adapter: ']],
            $answer->getErrors(),
        );
    }
}
