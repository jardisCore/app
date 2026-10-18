<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\ExternalCallServiceDemoContext;
use JardisSupport\Workflow\Data\NodeResult;
use Throwable;
use Ecommerce\Sales\Service\CallPricingApiBc as CallPricingApiBcService;

/**
 * Node CallPricingApiBc of the process ExternalCallServiceDemo (call).
 *
 * Ebene "bc": der Service-Stub landet unter {Domain}/{BC}/Service/.
 *
 * Calls the external service CallPricingApiBc.
 */
final class CallPricingApiBc extends EcommerceContext
{
    /**
     * @node-id e2b3c402
     * @throws Throwable
     */
    public function __invoke(ExternalCallServiceDemoContext $ctx): NodeResult
    {
        return $this->handle(CallPricingApiBcService::class)($ctx);
    }
}
