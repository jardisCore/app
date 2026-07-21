<?php

declare(strict_types=1);

namespace Ecommerce\Service;

use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * Ebene "domain": der Service-Stub landet unter Domain/{Domain}/Service/.
 *
 * Externer-Call, Ebene: domain.
 *
 * Fehlervertrag: Transportfehler (Timeout, Non-2xx-Antwort, Netzwerkfehler)
 * werden vom Aufrufer in die Workflow-Routing-Status ON_FAIL/ON_TIMEOUT
 * uebersetzt (Dev-/KI-Hoheit). Retry ist bereits eingebaut: jardisadapter/
 * http's HttpClient wiederholt automatisch bei 5xx-Antworten und
 * Netzwerkfehlern, gesteuert ueber ClientConfig::maxRetries (Default 0 =
 * kein Retry).
 */
final class CallPricingApiDomain extends EcommerceContext
{
    /**
     * @return array{status: string, data: array<string, mixed>}
     * @throws RuntimeException
     */
    public function __invoke(WorkflowContextInterface $context): array
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
