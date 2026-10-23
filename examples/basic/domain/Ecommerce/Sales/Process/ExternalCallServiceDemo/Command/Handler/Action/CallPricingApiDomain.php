<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\ExternalCallServiceDemoContext;
use Ecommerce\Sales\Service\CallPricingApiDomain as ServiceCallPricingApiDomain;
use JardisSupport\Workflow\Data\NodeResult;
use Throwable;

/**
 * Node CallPricingApiDomain of the process ExternalCallServiceDemo (call).
 *
 * Dritter Externer Call: auch dieser Stub landet unter {Domain}/{BC}/Service/.
 *
 * Calls the external service CallPricingApiDomain.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: ExternalCallServiceDemo
 *     Externer-Call-Fixture (docs/cross-bc-process-access P4, AK2): drei
 *     Externer-Call-Knoten, jeder mit eigenem Service; alle Stubs landen unter
 *     {Domain}/{BC}/Service/ — CallPricingApiProcess, CallPricingApiBc,
 *     CallPricingApiDomain. Jeder Knoten deklariert einen Service mit demselben
 *     Namen wie der Knoten selbst (Alias-Guard-Beleg, analog CrossBcServiceDemo P3).
 * Knoten: CallPricingApiDomain
 */
final class CallPricingApiDomain extends EcommerceContext
{
    /**
     * @node-id e3c4d503
     * @throws Throwable
     */
    public function __invoke(ExternalCallServiceDemoContext $ctx): NodeResult
    {
        return $this->handle(ServiceCallPricingApiDomain::class)->__invoke($ctx);
    }
}
