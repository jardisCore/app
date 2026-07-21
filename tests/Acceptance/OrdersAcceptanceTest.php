<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Acceptance;

use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * P7 §5 core e2e matrix against the REAL App pipeline + vendored, generated
 * Ecommerce fixture over MySQL: `GET/POST/PATCH /orders` (F6 outer door),
 * Health (F5/M10), and the F9 boundary cases 404/405/500 plus the real
 * `ParseJsonBody` 400 path (F8c).
 */
final class OrdersAcceptanceTest extends EcommerceAcceptanceTestCase
{
    private const VALID_ORDER_PAYLOAD = [
        'orderNumber' => 'ORD-ACC-001',
        'totalAmount' => 49.98,
        'status' => 'pending',
        'customer' => [
            'email' => 'acceptance@example.com',
            'customerName' => 'Acceptance Tester',
            'phone' => '+49 123 456',
            'address' => [
                'street' => 'Main Street 1',
                'city' => 'Berlin',
                'postalCode' => '10115',
                'country' => 'DE',
            ],
        ],
        'orderItem' => [
            ['productIdentifier' => 'PROD-001', 'quantity' => 2, 'unitPrice' => 19.99, 'subtotal' => 39.98],
        ],
    ];

    public function testHealthRespondsOkWithoutTouchingTheDomain(): void
    {
        $app = $this->buildApp();

        $response = $app->handle($this->request('GET', '/health'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"status":200}', (string) $response->getBody());
    }

    public function testPostOrdersCreatesAnOrderAndReturnsTheCanonicalTwoHundredOneEnvelope(): void
    {
        $app = $this->buildApp();

        $response = $app->handle($this->jsonRequest('POST', '/orders', self::VALID_ORDER_PAYLOAD));

        $this->assertSame(201, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame(201, $body['status']);
        // The generated ContextResponse wraps every response's data under
        // its own context (class-name) key (verified empirically against
        // the real fixture, `Ecommerce\Response\ContextResponse::getData()`)
        // — MapDomainResponse passes it through unchanged (F4 pass-through
        // by design), so the confirmed order surfaces one level under
        // `GetOrderByOrderNumberHandler` rather than directly under `data`.
        $this->assertSame(
            'ORD-ACC-001',
            $body['data']['GetOrderByOrderNumberHandler']['order']['orderNumber'] ?? null,
        );
    }

    public function testPostOrdersWithAValidationRejectedOrderDoesNotFalselyReportCreated(): void
    {
        $app = $this->buildApp();
        $payload = self::VALID_ORDER_PAYLOAD;
        $payload['customer']['phone'] = '1'; // fails ValidateCreateOrder's phone check

        $response = $app->handle($this->jsonRequest('POST', '/orders', $payload));

        // The process itself would report success here (verified
        // empirically — see the comment on RegisterOrderRoutes::createOrder)
        // ; the read-facade confirmation is what catches it.
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(400, $this->decode($response)['status']);
    }

    public function testGetOrderByIdReturnsTheJustCreatedOrder(): void
    {
        $app = $this->buildApp();
        $app->handle($this->jsonRequest('POST', '/orders', self::VALID_ORDER_PAYLOAD));

        $response = $app->handle($this->request('GET', '/orders/1'));

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame(200, $body['status']);
        $this->assertSame('ORD-ACC-001', $body['data']['GetOrderByIdHandler']['order']['orderNumber'] ?? null);
        $this->assertSame(49.98, $body['data']['GetOrderByIdHandler']['order']['totalAmount'] ?? null);
    }

    public function testPatchOrdersWithNonPositiveTotalAmountReturnsTheRealRuleViolationPayload(): void
    {
        $app = $this->buildApp();
        $app->handle($this->jsonRequest('POST', '/orders', self::VALID_ORDER_PAYLOAD));

        $response = $app->handle($this->jsonRequest('PATCH', '/orders/ORD-ACC-001', [
            'totalAmount' => 0.0,
            'status' => 'pending',
        ]));

        $this->assertSame(422, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame(422, $body['status']);
        // {rule, messageKey, context} unter `data.UpdateOrder` (siehe
        // Kommentar in testPostOrders... oben) — Struktur, Schluessel und
        // Werte sind der reale generierte Vertrag (E2 RuleViolation
        // pass-through), unveraendert.
        $this->assertSame('OrderUpdateAllowed', $body['data']['UpdateOrder']['rule'] ?? null);
        $this->assertSame(
            'rule.order_update_allowed.non_positive_total',
            $body['data']['UpdateOrder']['messageKey'] ?? null,
        );
        // json_encode(0.0) drops the fractional part ("0", not "0.0")
        // without JSON_PRESERVE_ZERO_FRACTION, so json_decode round-trips
        // it back as int(0) here — real PHP JSON behaviour, not a bug;
        // the assertion matches what actually comes back over the wire.
        $this->assertSame(0, $body['data']['UpdateOrder']['context']['totalAmount'] ?? null);
    }

    public function testPatchOrdersWithAPositiveTotalAmountSucceeds(): void
    {
        $app = $this->buildApp();
        $app->handle($this->jsonRequest('POST', '/orders', self::VALID_ORDER_PAYLOAD));

        $response = $app->handle($this->jsonRequest('PATCH', '/orders/ORD-ACC-001', [
            'totalAmount' => 75.0,
            'status' => 'pending',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame('ORD-ACC-001', $body['data']['UpdateOrder']['orderNumber'] ?? null);
    }

    public function testUnknownRouteRespondsWithTheCanonicalFourOhFourEnvelope(): void
    {
        $app = $this->buildApp();

        $response = $app->handle($this->request('GET', '/does-not-exist'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(404, $this->decode($response)['status']);
    }

    public function testUnsupportedMethodOnOrdersRespondsWithFourOhFiveAndTheAllowHeader(): void
    {
        $app = $this->buildApp();

        $response = $app->handle($this->request('DELETE', '/orders/1'));

        $this->assertSame(405, $response->getStatusCode());
        $allow = array_map('trim', explode(',', $response->getHeaderLine('Allow')));
        $this->assertEqualsCanonicalizing(['GET', 'HEAD', 'PATCH'], $allow);
    }

    public function testHandlerExceptionRespondsWithAGenericFiveHundred(): void
    {
        $app = $this->buildAppWithSyntheticRoutes();

        $response = $app->handle($this->request('GET', '/test/boom'));

        $this->assertSame(500, $response->getStatusCode());
        $json = (string) $response->getBody();
        $this->assertStringNotContainsString('synthetic handler exception', $json);
    }

    public function testARealDomainTriggeredTechnicalFaultAlsoSurfacesAsFiveHundred(): void
    {
        $app = $this->buildApp();
        $payload = self::VALID_ORDER_PAYLOAD;
        $payload['orderNumber'] = 'ORDER-TRIGGER-FAULT';

        $response = $app->handle($this->jsonRequest('POST', '/orders', $payload));

        $this->assertSame(500, $response->getStatusCode());
    }

    public function testInvalidJsonBodyOnPostOrdersGoesThroughTheRealParseJsonBodyFourHundredPath(): void
    {
        $app = $this->buildApp();

        $request = (new Psr17Factory())
            ->createServerRequest('POST', '/orders')
            ->withHeader('Content-Type', 'application/json')
            ->withBody((new Psr17Factory())->createStream('{not-valid-json'));

        $response = $app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(400, $this->decode($response)['status']);
    }
}
