<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler\CrossBcReadDemoContext;
use Ecommerce\Service\CrossBcReadDemoLoadCatalogProduct;
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
 */
final class LoadCatalogProduct extends EcommerceContext
{
    /**
     * @node-id r1a2b301
     * @throws Throwable
     */
    public function __invoke(CrossBcReadDemoContext $ctx): NodeResult
    {
        return $this->handle(CrossBcReadDemoLoadCatalogProduct::class)($ctx);
    }
}
