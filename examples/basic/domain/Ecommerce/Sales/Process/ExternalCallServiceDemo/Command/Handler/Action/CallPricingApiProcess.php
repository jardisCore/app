<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\ExternalCallServiceDemoContext;
use JardisSupport\Workflow\Data\NodeResult;
use Throwable;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Service\CallPricingApiProcess as CallPricingApiProcessService;

/**
 * Node CallPricingApiProcess of the process ExternalCallServiceDemo (call).
 *
 * Ebene "process" (Default): der Service-Stub landet unter
 * {BC}/Process/{ProcessName}/Service/.
 *
 * Calls the external service CallPricingApiProcess.
 */
final class CallPricingApiProcess extends EcommerceContext
{
    /**
     * @node-id e1a2b301
     * @throws Throwable
     */
    public function __invoke(ExternalCallServiceDemoContext $ctx): NodeResult
    {
        return $this->handle(CallPricingApiProcessService::class)($ctx);
    }
}
