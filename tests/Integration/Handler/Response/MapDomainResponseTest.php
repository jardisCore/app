<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Response;

use JardisCore\App\Handler\Response\MapDomainResponse;
use JardisCore\App\Tests\Support\DomainResponse;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MapDomainResponse (F4): the canonical DomainResponseInterface
 * -> PSR-7 mapper, exercised against the Contract-Fake `DomainResponse`
 * (E15) for every one of the 11 ResponseStatus cases.
 *
 * Covers: HTTP-code + envelope for all cases; E2b object-coercion as a
 * STRING assertion; the E2c 204-no-body exception; byte-treu 422
 * `{rule,messageKey,context}` pass-through.
 */
final class MapDomainResponseTest extends TestCase
{
    private function map(): MapDomainResponse
    {
        $factory = new Psr17Factory();

        return new MapDomainResponse($factory, $factory);
    }

    /**
     * @return list<array{ResponseStatus}>
     */
    public static function allStatusCasesProvider(): array
    {
        return array_map(static fn (ResponseStatus $case): array => [$case], ResponseStatus::cases());
    }

    #[DataProvider('allStatusCasesProvider')]
    public function testEveryResponseStatusCaseMapsToItsHttpCodeAndEnvelope(ResponseStatus $status): void
    {
        $map = $this->map();
        $domainResponse = new DomainResponse(
            status: $status->value,
            data: ['id' => '42'],
            errors: ['field' => 'must not be blank'],
            metadata: ['duration' => 12],
        );

        $response = $map($domainResponse);

        $this->assertSame($status->value, $response->getStatusCode());

        if ($status === ResponseStatus::NoContent) {
            $this->assertSame(0, $response->getBody()->getSize());

            return;
        }

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($status->value, $body['status']);
        $this->assertSame(['id' => '42'], $body['data']);
        $this->assertSame(['field' => 'must not be blank'], $body['errors']);
        $this->assertSame(['duration' => 12], $body['meta']);
    }

    public function testEmptyDataErrorsAndMetaAreSerializedAsJsonObjectsNotArrays(): void
    {
        $map = $this->map();
        $domainResponse = new DomainResponse(status: ResponseStatus::Success->value);

        $response = $map($domainResponse);
        $json = (string) $response->getBody();

        $this->assertStringContainsString('"data":{}', $json);
        $this->assertStringContainsString('"errors":{}', $json);
        $this->assertStringContainsString('"meta":{}', $json);
    }

    public function testNoContentResponseHasNoBodyAndNoContentTypeHeader(): void
    {
        $map = $this->map();
        $domainResponse = new DomainResponse(status: ResponseStatus::NoContent->value, data: ['ignored' => true]);

        $response = $map($domainResponse);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(0, $response->getBody()->getSize());
        $this->assertSame('', (string) $response->getBody());
        $this->assertFalse($response->hasHeader('Content-Type'));
    }

    public function testRuleViolation422PayloadIsPassedThroughByteTreu(): void
    {
        $map = $this->map();
        $payload = [
            'rule' => 'OrderMustNotBeShipped',
            'messageKey' => 'order.already_shipped',
            'context' => ['orderId' => '42'],
        ];
        $domainResponse = new DomainResponse(status: ResponseStatus::RuleViolation->value, data: $payload);

        $response = $map($domainResponse);

        $this->assertSame(422, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($payload, $body['data']);
    }
}
