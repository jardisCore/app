<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\CrossBcServiceDemoContext;
use JardisSupport\Workflow\Data\NodeResult;
use Throwable;
use Ecommerce\Service\CheckStockInCatalog as CheckStockInCatalogService;

/**
 * Node CheckStockInCatalog of the process CrossBcServiceDemo (call).
 *
 * Same-Domain-Variante (D3): schreibt in die fremde BC Catalog im eigenen
 * Domain Ecommerce ueber deren Prozess UpdateProductInCatalog (G7), nicht
 * direkt ueber das Aggregat. Der Domain-Service uebersetzt die eigene
 * Eingabe (productIdentifier + newPrice) in das fremde Prozess-Input-DTO
 * und mappt die Antwort ins eigene Vokabular zurueck (ACL).
 *
 * Calls the facade Ecommerce.Catalog.UpdateProductInCatalog, method updateProductInCatalog.
 */
final class CheckStockInCatalog extends EcommerceContext
{
    /**
     * @node-id a1b2c301
     * @throws Throwable
     */
    public function __invoke(CrossBcServiceDemoContext $ctx): NodeResult
    {
        return $this->handle(CheckStockInCatalogService::class)($ctx);
    }
}
