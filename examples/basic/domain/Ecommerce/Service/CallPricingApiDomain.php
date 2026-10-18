<?php

declare(strict_types=1);

namespace Ecommerce\Service;

use Ecommerce\EcommerceContext;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Ebene "domain": der Service-Stub landet unter Domain/{Domain}/Service/.
 *
 * Externer-Call, Ebene: domain.
 *
 * Fehlervertrag: Transportfehler (Timeout, Non-2xx-Antwort, Netzwerkfehler)
 * werden vom Dev-Koerper in die Routen Route::OnFalse/OnTimeout
 * uebersetzt (Dev-/KI-Hoheit). Retry ist bereits eingebaut: jardisadapter/
 * http's HttpClient wiederholt automatisch bei 5xx-Antworten und
 * Netzwerkfehlern, gesteuert ueber ClientConfig::maxRetries (Default 0 =
 * kein Retry).
 *
 * Antwort: ein NodeResult (jardissupport/workflow). NodeResult::ok() bei Erfolg,
 * NodeResult::route(Route::OnFalse|OnTimeout|OnSkip, ...) fuer einen Zweig,
 * NodeResult::reject(<4xx-Status>, <messageKey>, <context>) fuer eine Ablehnung.
 * Die Fehlerform baut das Paket aus dem Kontext: 400 'fields', 409 'reason' /
 * 'aggregate' / 'violated', 422 'rule'; weitere Schluessel bleiben Kontext der Form.
 * Ein 5xx-Fall ist eine Exception, kein reject().
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
