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
 * Zweiter Externer Call: auch dieser Stub landet unter {Domain}/{BC}/Service/.
 *
 * Calls the external service CallPricingApiBc.
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
 * Knoten: CallPricingApiBc
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
