<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Service;

use Ecommerce\EcommerceContext;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Dritter Externer Call: auch dieser Stub landet unter {Domain}/{BC}/Service/.
 *
 * Externer-Call.
 *
 * Fehlervertrag: Transportfehler (Timeout, Non-2xx-Antwort, Netzwerkfehler)
 * werden vom Dev-Koerper in die Routen Route::OnFalse/OnTimeout
 * uebersetzt (Dev-/KI-Hoheit). Retry ist bereits eingebaut: jardisadapter/
 * http's HttpClient wiederholt automatisch bei 5xx-Antworten und
 * Netzwerkfehlern, gesteuert ueber ClientConfig::maxRetries (Default 0 =
 * kein Retry).
 *
 * Mandant und Nutzer des Laufs: $context->input()->tenant, $context->input()->user
 * (beide string); wer fremde Lese- oder Schreibtueren ruft, reicht sie dort durch.
 *
 * Antwort: ein NodeResult (jardissupport/workflow). NodeResult::ok() bei Erfolg,
 * NodeResult::route(Route::OnFalse|OnTimeout|OnSkip, ...) fuer einen Zweig,
 * NodeResult::reject(<4xx-Status>, <messageKey>, <context>) fuer eine Ablehnung.
 * Die Fehlerform baut das Paket aus dem Kontext: 400 'fields', 409 'reason' /
 * 'aggregate' / 'violated', 422 'rule'; weitere Schluessel bleiben Kontext der Form.
 * Ein 5xx-Fall ist eine Exception, kein reject().
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Service: CallPricingApiDomain
 */
final class CallPricingApiDomain extends EcommerceContext
{
    /**
     * @throws \RuntimeException
     */
    public function __invoke(ProcessContext $context): NodeResult
    {
        $client = $this->resource()->httpClient();
        if ($client === null) {
            throw new \RuntimeException(
                'Kein PSR-18 HTTP-Client konfiguriert (DomainKernelInterface::httpClient()): ' . self::class
            );
        }

        throw new \RuntimeException(
            'Not implemented: write the externen Systemaufruf fuer ' . self::class
        );
    }
}
