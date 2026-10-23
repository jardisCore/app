<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\ExternalCallServiceDemoContext;
use Ecommerce\Sales\Service\CallPricingApiProcess as ServiceCallPricingApiProcess;
use JardisSupport\Workflow\Data\NodeResult;
use Throwable;

/**
 * Node CallPricingApiProcess of the process ExternalCallServiceDemo (call).
 *
 * Der Service-Stub landet unter {Domain}/{BC}/Service/ des aufrufenden BC.
 *
 * Calls the external service CallPricingApiProcess.
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
 * Knoten: CallPricingApiProcess
 */
final class CallPricingApiProcess extends EcommerceContext
{
    /**
     * @node-id e1a2b301
     * @throws Throwable
     */
    public function __invoke(ExternalCallServiceDemoContext $ctx): NodeResult
    {
        return $this->handle(ServiceCallPricingApiProcess::class)->__invoke($ctx);
    }
}
