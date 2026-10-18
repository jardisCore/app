<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\ExternalCallServiceDemoContext;
use JardisSupport\Workflow\Data\NodeResult;
use Throwable;
use Ecommerce\Service\CallPricingApiDomain as CallPricingApiDomainService;

/**
 * Node CallPricingApiDomain of the process ExternalCallServiceDemo (call).
 *
 * Ebene "domain": der Service-Stub landet unter Domain/{Domain}/Service/.
 *
 * Calls the external service CallPricingApiDomain.
 */
final class CallPricingApiDomain extends EcommerceContext
{
    /**
     * @node-id e3c4d503
     * @throws Throwable
     */
    public function __invoke(ExternalCallServiceDemoContext $ctx): NodeResult
    {
        return $this->handle(CallPricingApiDomainService::class)($ctx);
    }
}
