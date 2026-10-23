<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler\CrossBcReadDemoContext;
use Ecommerce\Sales\Service\CatalogProductGetProductByIdentifier;
use JardisSupport\Workflow\Data\NodeResult;
use Throwable;

/**
 * Node LoadCatalogProduct of the process CrossBcReadDemo (call).
 *
 * Liest ein Product der fremden BC Catalog ueber deren Lese-Fassade
 * ($catalog->product()->getProductByIdentifier()). Der Service uebersetzt die
 * Antwort ins eigene Vokabular (ACL).
 *
 * Calls the facade Ecommerce.Catalog.Product, method getProductByIdentifier.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: CrossBcReadDemo
 *     Cross-BC-Lese-Fixture (crm-nachlauf E3.9): ein Lese-Knoten mit consumedCalls
 *     auf die Lese-Fassade einer fremden BC (Ecommerce.Sales -> Ecommerce.Catalog)
 *     OHNE ausdruecklichen crossBcCall. Die Knoten-Art ist BC-bewusst: der Knoten ist
 *     ein Cross-BC-Knoten, sein Service-Name wird aus dem Ziel abgeleitet (Stub unter {BC}/Service/), die
 *     Action importiert nichts aus Catalog und delegiert ueber die Kernel-Naht an das
 *     Service-Scaffold, das die Lese-Fassade des fremden BC verdrahtet.
 * Knoten: LoadCatalogProduct
 */
final class LoadCatalogProduct extends EcommerceContext
{
    /**
     * @node-id r1a2b301
     * @throws Throwable
     */
    public function __invoke(CrossBcReadDemoContext $ctx): NodeResult
    {
        return $this->handle(CatalogProductGetProductByIdentifier::class)->__invoke($ctx);
    }
}
