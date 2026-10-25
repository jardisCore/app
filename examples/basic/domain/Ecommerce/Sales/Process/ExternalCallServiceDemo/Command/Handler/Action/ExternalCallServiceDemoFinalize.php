<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\ExternalCallServiceDemoContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node ExternalCallServiceDemoFinalize of the process ExternalCallServiceDemo (action).
 *
 * Platzhalter-Abschluss (onFail-Zweig) — bleibt bewusst ein Wurf-Stub
 * (kein consumedCall/externalCall) — KI-/Dev-Hoheit.
 *
 * Mandant und Nutzer des Laufs: $ctx->input()->tenant, $ctx->input()->user (beide string).
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
 * Knoten: ExternalCallServiceDemoFinalize
 */
final class ExternalCallServiceDemoFinalize extends EcommerceContext
{
    /**
     * @node-id e4d5e604
     */
    public function __invoke(ExternalCallServiceDemoContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}
