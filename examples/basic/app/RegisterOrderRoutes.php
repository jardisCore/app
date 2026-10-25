<?php

declare(strict_types=1);

namespace ExampleApp;

use Closure;
use Ecommerce\Sales\Model\Order\Command\Address as CommandAddress;
use Ecommerce\Sales\Model\Order\Command\Customer as CommandCustomer;
use Ecommerce\Sales\Model\Order\Command\ItemDiscount as CommandItemDiscount;
use Ecommerce\Sales\Model\Order\Command\Order as CommandOrder;
use Ecommerce\Sales\Model\Order\Command\OrderItem as CommandOrderItem;
use Ecommerce\Sales\Model\Order\Command\UpdateOrder as CommandUpdateOrder;
use Ecommerce\Sales\Model\Order\Query\OrderById;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\RuleGuardedOrderIntake;
use Ecommerce\Sales\Sales;
use ExampleApp\Support\StaticDomainResponse;
use JardisCore\App\Handler\Request\ParseJsonBody;
use JardisCore\App\Handler\Request\ReadRequestAttribute;
use JardisCore\App\Routes;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers the Ecommerce "orders" routes (PLAN P7 §5 core): the Routen-
 * Mapping fixed in PLAN.md — `GET /orders/{id}` (read facade), `POST
 * /orders` (process), `PATCH /orders/{id}` (rule-guarded exposed command).
 *
 * Every handler here uses ONLY the Sales BC's legitimate outer door
 * (F6): `order()` (read facade), `process()` (process facade),
 * `updateOrder()` (exposed rule-guarded command) — never the aggregate
 * WRITE facade, a Command/Query Handler, a Repository, or a Rule class
 * directly. `examples/basic/phpstan-handlers.neon` enforces this
 * statically (P7 Block C); `examples/basic/negative-fixture/` shows what
 * gets caught.
 *
 * Closure-Orchestrator note: this class is example/demo glue, not a
 * package Orchestrator — its single `__invoke` composes three private
 * per-route builders, mirroring the constitution's shape without being
 * bound by the `src/` package rules (this directory is
 * `examples/basic/app/`, not `src/`).
 */
final class RegisterOrderRoutes
{
    public function __invoke(Routes $routes, Sales $sales): void
    {
        $routes->get('/orders/{id}', $this->getOrder($sales));
        $routes->post('/orders', $this->createOrder($sales));
        $routes->patch('/orders/{id}', $this->updateOrder($sales));
    }

    /**
     * `GET /orders/{id}` — F8a path parameter, cast to int (F8: typed
     * coercion is the handler's job in v1), straight through the Order
     * aggregate's read facade.
     */
    private function getOrder(Sales $sales): Closure
    {
        return static function (ServerRequestInterface $request) use ($sales): DomainResponseInterface {
            $tenant = (new ReadRequestAttribute())($request, RequestAttribute::TENANT);
            $id = (int) $request->getAttribute('id');

            return $sales->order()->getOrderById(new OrderById($tenant, $id));
        };
    }

    /**
     * `POST /orders` — creates via the process facade's
     * `ruleGuardedOrderIntake` (the only legitimate outer door for
     * creating an Order; the aggregate write facade is family-internal
     * only, PRD F6/generated-code-wire-transport). The process answers
     * every failure in the package's envelope (400 fields, 409, 422,
     * 5xx) - those pass through unchanged. On success the order is
     * reloaded through the read facade (a write answers with references,
     * the caller reads the object) and answered as 201 Created; the
     * data of both answers is flat, the business object itself with its
     * `@type`.
     */
    private function createOrder(Sales $sales): Closure
    {
        return static function (ServerRequestInterface $request) use ($sales): DomainResponseInterface {
            $body = (new ParseJsonBody())($request);
            $readAttribute = new ReadRequestAttribute();
            $tenant = $readAttribute($request, RequestAttribute::TENANT);
            $user = $readAttribute($request, RequestAttribute::SUBJECT_ID);
            $command = self::buildOrderCommand($tenant, $body);

            $response = $sales->process()->ruleGuardedOrderIntake(new RuleGuardedOrderIntake($command, $tenant, $user));

            if (!$response->isSuccess()) {
                return $response;
            }

            $confirmed = $sales->order()->getOrderByOrderNumber(new OrderByOrderNumber($tenant, $command->orderNumber));

            if (!$confirmed->isSuccess()) {
                return $confirmed;
            }

            return new StaticDomainResponse(
                status: ResponseStatus::Created->value,
                data: $confirmed->getData(),
            );
        };
    }

    /**
     * `PATCH /orders/{id}` — the {id} path segment addresses by
     * `orderNumber` (the business key `UpdateOrder`'s own DTO uses, E3),
     * not the numeric id `GET /orders/{id}` reads by. Rule violation
     * (`totalAmount <= 0`, `OrderUpdateAllowed`) surfaces as the real
     * generated 422 `{rule, messageKey, context}` payload untouched.
     */
    private function updateOrder(Sales $sales): Closure
    {
        return static function (ServerRequestInterface $request) use ($sales): DomainResponseInterface {
            $orderNumber = (string) $request->getAttribute('id');
            $body = (new ParseJsonBody())($request);

            $update = new CommandUpdateOrder(
                tenant: (new ReadRequestAttribute())($request, RequestAttribute::TENANT),
                orderNumber: $orderNumber,
                totalAmount: (string) ($body['totalAmount'] ?? '0'),
                status: (string) ($body['status'] ?? ''),
            );

            return $sales->updateOrder($update);
        };
    }

    /**
     * F8: request -> Domain-DTO translation is the thin handler's job.
     * Builds the nested `CommandOrder` DTO from a decoded JSON body.
     *
     * @param array<string, mixed> $body
     */
    private static function buildOrderCommand(string $tenant, array $body): CommandOrder
    {
        /** @var array<string, mixed> $customer */
        $customer = (array) ($body['customer'] ?? []);
        /** @var array<string, mixed> $address */
        $address = (array) ($customer['address'] ?? []);
        /** @var list<array<string, mixed>> $items */
        $items = (array) ($body['orderItem'] ?? []);

        return new CommandOrder(
            tenant: $tenant,
            orderNumber: (string) ($body['orderNumber'] ?? ''),
            totalAmount: (string) ($body['totalAmount'] ?? '0'),
            status: (string) ($body['status'] ?? 'pending'),
            customer: new CommandCustomer(
                email: (string) ($customer['email'] ?? ''),
                customerName: (string) ($customer['customerName'] ?? ''),
                phone: isset($customer['phone']) ? (string) $customer['phone'] : null,
                address: new CommandAddress(
                    street: (string) ($address['street'] ?? ''),
                    city: (string) ($address['city'] ?? ''),
                    postalCode: (string) ($address['postalCode'] ?? ''),
                    country: (string) ($address['country'] ?? ''),
                ),
            ),
            orderItem: array_map(self::buildOrderItem(...), $items),
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function buildOrderItem(array $item): CommandOrderItem
    {
        /** @var list<array<string, mixed>> $discounts */
        $discounts = (array) ($item['itemDiscount'] ?? []);

        return new CommandOrderItem(
            productIdentifier: (string) ($item['productIdentifier'] ?? ''),
            quantity: (int) ($item['quantity'] ?? 0),
            unitPrice: (string) ($item['unitPrice'] ?? '0'),
            subtotal: (string) ($item['subtotal'] ?? '0'),
            itemDiscount: array_map(self::buildItemDiscount(...), $discounts),
        );
    }

    /**
     * @param array<string, mixed> $discount
     */
    private static function buildItemDiscount(array $discount): CommandItemDiscount
    {
        return new CommandItemDiscount(
            discountCode: (string) ($discount['discountCode'] ?? ''),
            amount: (string) ($discount['amount'] ?? '0'),
        );
    }
}
