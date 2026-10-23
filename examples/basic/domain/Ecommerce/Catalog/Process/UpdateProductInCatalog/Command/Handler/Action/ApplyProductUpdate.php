<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler\Action;

use Ecommerce\Catalog\Model\Product\Product;
use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler\UpdateProductInCatalogContext;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Handler\Node\MapResponse;
use Throwable;

/**
 * Node ApplyProductUpdate of the process UpdateProductInCatalog (call).
 *
 * Persistiert die Product-Aenderung ueber die Aggregat-API
 * (updateProduct). onFail bei fachlichem Misserfolg der
 * DomainResponse.
 *
 * Calls the facade Ecommerce.Catalog.Product, method UpdateProduct.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Catalog
 * Prozess: UpdateProductInCatalog
 *     Prozess-Ziel für Fremd-Writes auf Ecommerce.Catalog.Product
 *     (bc-facade-layering PRD.md G7, P3): kapselt den Aggregat-Command
 *     UpdateProduct hinter einem Prozess, damit CrossBcServiceDemo
 *     (Ecommerce/Sales) über process() statt direkt über das fremde Aggregat
 *     schreibt. Minimal autoriert (P3) — die echte DTO-Übersetzung im
 *     Demo-Service (CheckStockInCatalog) folgt P4.
 * Knoten: ApplyProductUpdate
 */
final class ApplyProductUpdate extends EcommerceContext
{
    /**
     * @node-id cp1a0f01
     * @throws Throwable
     */
    public function __invoke(UpdateProductInCatalogContext $ctx): NodeResult
    {
        /** @var DomainResponseInterface $response */
        $response = $this->handle(Product::class)->updateProduct($ctx->input()->update);

        return (new MapResponse())($response);
    }
}
