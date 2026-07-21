<?php

declare(strict_types=1);

/**
 * F6 negative fixture (PLAN P7 Block C): demonstrates the exact
 * "Umgehungsversuch" the architecture rule must catch — a route handler
 * reaching PAST the Sales BC's legitimate outer door (`order()`,
 * `process()`, `updateOrder()`) straight into the aggregate WRITE facade
 * (`Ecommerce\Sales\Aggregate\Order\Order`, family-internal per
 * `platform-usage`/PRD F6).
 *
 * Deliberately NOT mapped by any Composer `autoload-dev` PSR-4 prefix (no
 * rule targets `examples/basic/negative-fixture/`) — it never loads into
 * the main QA autoloader (`make phpunit`/`make phpstan`/`make phpcs`
 * against `src/`); only `ForbidDomainInternalsRuleTest` ever points
 * PHPStan at this file directly, asserting the analysis exits non-zero.
 */

namespace ExampleApp\NegativeFixture;

use Ecommerce\Sales\Aggregate\Order\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Order as CommandOrder;
use JardisSupport\Contract\Kernel\DomainKernelInterface;

final class BypassesAggregateWriteFacade
{
    public function handle(DomainKernelInterface $kernel, CommandOrder $command): void
    {
        // F6 violation: bypasses Sales::process()/Sales::updateOrder() and
        // instantiates the family-internal write facade directly.
        $order = new Order($kernel);
        $order->createOrder($command);
    }
}
