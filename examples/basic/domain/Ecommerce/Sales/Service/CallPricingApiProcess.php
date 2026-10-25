<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Service;

use Ecommerce\EcommerceContext;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;
use JardisSupport\Workflow\Data\Route;

/**
 * Der Service-Stub landet unter {Domain}/{BC}/Service/ des aufrufenden BC.
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
 * Service: CallPricingApiProcess
 */
final class CallPricingApiProcess extends EcommerceContext
{
    /**
     * Test-fixture hook (format-haertung A3, AK4): forces this Service to
     * return Route::OnTimeout — a route this Fehlervertrag names as an expected
     * translation target, but one `ExternalCallServiceDemo.yml`'s edges never
     * route for this node (only onSuccess/onFail are mapped). Under the
     * Builder's now-unconditional `strictRouting`, this reproduces exactly
     * the bug class strict routing exists to catch — a documented-possible
     * status nobody wired a route for — so ExternalCallServiceDemoTest can
     * pin the engine's UnroutedStatusException reaching the generated
     * orchestrator's existing catch(Throwable) unmodified (AK4).
     */
    public static bool $triggerUnroutedTimeout = false;

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

        if (self::$triggerUnroutedTimeout) {
            return NodeResult::route(Route::OnTimeout, []);
        }

        throw new \RuntimeException(
            'Not implemented: write the externen Systemaufruf fuer ' . self::class
        );
    }
}
