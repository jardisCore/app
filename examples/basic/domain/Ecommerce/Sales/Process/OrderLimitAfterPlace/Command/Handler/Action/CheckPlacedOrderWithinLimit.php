<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\OrderTotalWithinLimit;
use Ecommerce\Sales\Model\Order\Repository\LoadOrderForRule;
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler\OrderLimitAfterPlaceContext;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\Route;
use Throwable;

/**
 * Node CheckPlacedOrderWithinLimit of the process OrderLimitAfterPlace (rule).
 *
 * Rule-Knoten (Langform): root <- Identitaet aus dem Ergebnis des Command-Knotens
 * n1, limit <- Eingang `limit`.
 *
 * Evaluates the rules OrderTotalWithinLimit.
 */
final class CheckPlacedOrderWithinLimit extends EcommerceContext
{
    /**
     * @node-id n2
     * @throws \LogicException
     * @throws Throwable
     */
    public function __invoke(OrderLimitAfterPlaceContext $ctx): NodeResult
    {
        $fromPlaceLimitedOrder = $ctx->placeLimitedOrder() ?? throw new \LogicException('PlaceLimitedOrder');

        $identifierRoot = $fromPlaceLimitedOrder->data()['orderNumber']
            ?? throw new \LogicException('PlaceLimitedOrder/orderNumber');
        $loadedRoot = $this->handle(LoadOrderForRule::class)($ctx->input()->tenant, $identifierRoot);

        if ($loadedRoot === null) {
            return NodeResult::reject(
                ResponseStatus::RuleViolation,
                'sales.order.notFound',
                ['identifier' => $identifierRoot]
            );
        }

        $result = $this->handle(OrderTotalWithinLimit::class)($loadedRoot, $ctx->input()->limit);
        if (!$result->passed) {
            return NodeResult::reject(
                ResponseStatus::RuleViolation,
                'sales.orderOverLimit',
                ['rule' => $result->rule] + $result->context
            );
        }

        return NodeResult::route(Route::OnTrue);
    }
}
