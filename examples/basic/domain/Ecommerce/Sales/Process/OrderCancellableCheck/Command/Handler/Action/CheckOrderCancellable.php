<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderCancellableCheck\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\OrderIsCancellable;
use Ecommerce\Sales\Model\Order\Repository\LoadOrderForRule;
use Ecommerce\Sales\Process\OrderCancellableCheck\Command\Handler\OrderCancellableCheckContext;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\Route;
use Throwable;

/**
 * Node CheckOrderCancellable of the process OrderCancellableCheck (rule).
 *
 * Rule-Knoten (I1/Sec8.3): prueft die Katalog-Rule OrderIsCancellable
 * (aggregate/Order) gegen die per orderNumberToCheck adressierte Wurzel.
 * Unbekannte Wurzel -> onFalse notFound; existierende, nicht stornierte
 * Wurzel -> onTrue.
 *
 * Evaluates the rules OrderIsCancellable.
 */
final class CheckOrderCancellable extends EcommerceContext
{
    /**
     * @node-id n1
     * @throws Throwable
     */
    public function __invoke(OrderCancellableCheckContext $ctx): NodeResult
    {
        $identifier = $ctx->input()->orderNumberToCheck;
        $loaded = $this->handle(LoadOrderForRule::class)($ctx->input()->tenant, $identifier);

        if ($loaded === null) {
            return NodeResult::reject(
                ResponseStatus::RuleViolation,
                'sales.order.notFound',
                ['identifier' => $identifier]
            );
        }

        $result = $this->handle(OrderIsCancellable::class)($loaded);
        if (!$result->passed) {
            return NodeResult::reject(
                ResponseStatus::RuleViolation,
                'sales.orderAlreadyCancelled',
                ['rule' => $result->rule] + $result->context
            );
        }

        return NodeResult::route(Route::OnTrue);
    }
}
