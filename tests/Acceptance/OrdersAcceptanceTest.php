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
        // The data is flat (`jardissupport/workflow` wire contract): the
        // reloaded order itself, its type in `@type`.
        $this->assertSame('order', $body['data']['@type'] ?? null);
        $this->assertSame('ORD-ACC-001', $body['data']['orderNumber'] ?? null);
    }

    public function testPostOrdersWithAnInvalidFieldAnswersTheFlatValidationEnvelope(): void
    {
        $app = $this->buildApp();
        $payload = self::VALID_ORDER_PAYLOAD;
        $payload['customer']['phone'] = '1'; // fails the phone check of the process start

        $response = $app->handle($this->jsonRequest('POST', '/orders', $payload));

        // The process answers the start failure itself (400, `fields` with
        // the path of the input field) - no order was written.
        $this->assertSame(400, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame(400, $body['status']);
        $this->assertSame('validation', $body['data']['@type'] ?? null);
        $this->assertSame('order.customer.phone', $body['data']['fields'][0]['field'] ?? null);
        $this->assertSame('validation.invalid', $body['data']['fields'][0]['messageKey'] ?? null);

        $pdo = self::$pdo;
        assert($pdo !== null);
        $count = $pdo->query('SELECT COUNT(*) FROM orders');
        $this->assertNotFalse($count);
        $this->assertSame('0', (string) $count->fetchColumn());
    }

    public function testGetOrderByIdReturnsTheJustCreatedOrder(): void
    {
        $app = $this->buildApp();
        $app->handle($this->jsonRequest('POST', '/orders', self::VALID_ORDER_PAYLOAD));

        $response = $app->handle($this->request('GET', '/orders/1'));

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame(200, $body['status']);
        $this->assertSame('order', $body['data']['@type'] ?? null);
        $this->assertSame('ORD-ACC-001', $body['data']['orderNumber'] ?? null);
        // Decimals travel as strings on the wire (no float rounding).
        $this->assertSame('49.98', $body['data']['totalAmount'] ?? null);
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
        // Flat 422 form of the process package (`@type`, `rule`, `messageKey`,
        // `context`; wire contract case 422) - the real generated contract.
        $this->assertSame('ruleViolation', $body['data']['@type'] ?? null);
        $this->assertSame('OrderUpdateAllowed', $body['data']['rule'] ?? null);
        $this->assertSame('rule.order_update_allowed.non_positive_total', $body['data']['messageKey'] ?? null);
        // The command carries the decimal as a string, so the context
        // carries the string the route handed over.
        $this->assertSame('0', $body['data']['context']['totalAmount'] ?? null);
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
        $this->assertSame('order', $body['data']['@type'] ?? null);
        $this->assertSame('ORD-ACC-001', $body['data']['orderNumber'] ?? null);
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
        $created = $app->handle($this->jsonRequest('POST', '/orders', $payload));
        $this->assertSame(201, $created->getStatusCode());

        // OrderIsCancellable (the guard of the update) throws on this order
        // number - a technical fault, never a 4xx.
        $response = $app->handle($this->jsonRequest('PATCH', '/orders/ORDER-TRIGGER-FAULT', [
            'totalAmount' => 75.0,
            'status' => 'pending',
        ]));

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
