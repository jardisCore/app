<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\OrderTotalWithinLimit;
use Ecommerce\Sales\Model\Order\Repository\LoadOrderForRule;
use Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler\OrderLimitCheckContext;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\Route;
use Throwable;

/**
 * Node CheckOrderTotalWithinLimit of the process OrderLimitCheck (rule).
 *
 * Rule-Knoten (Langform): root <- Ergebnis von n1.orderNumber, limit <- Eingang
 * `limit`.
 *
 * Evaluates the rules OrderTotalWithinLimit.
 */
final class CheckOrderTotalWithinLimit extends EcommerceContext
{
    /**
     * @node-id n2
     * @throws \LogicException
     * @throws Throwable
     */
    public function __invoke(OrderLimitCheckContext $ctx): NodeResult
    {
        $fromResolveOrderNumber = $ctx->resolveOrderNumber() ?? throw new \LogicException('ResolveOrderNumber');

        $identifierRoot = $fromResolveOrderNumber->orderNumber;
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
