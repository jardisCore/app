<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Process;

use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler\UpdateProductInCatalogHandler;
use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\UpdateProductInCatalog;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Ecommerce\EcommerceContext;

/**
 * CatalogProcess — bounded-context process facade.
 *
 * Bundles every process of the bounded context behind one facade,
 * reached via $bc->process(). Each method dispatches to the process
 * orchestrator. Fully generated — overwritten on every rebuild; the
 * process logic lives in the orchestrator handler and its Action nodes,
 * not here.
 */
final class CatalogProcess extends EcommerceContext
{
    /**
     * Prozess-Ziel für Fremd-Writes auf Ecommerce.Catalog.Product
     * (bc-facade-layering PRD.md G7, P3): kapselt den Aggregat-Command
     * UpdateProduct hinter einem Prozess, damit CrossBcServiceDemo
     * (Ecommerce/Sales) über process() statt direkt über das fremde Aggregat
     * schreibt. Minimal autoriert (P3) — die echte DTO-Übersetzung im
     * Demo-Service (CheckStockInCatalog) folgt P4.
     *
     */
    public function updateProductInCatalog(UpdateProductInCatalog $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(UpdateProductInCatalogHandler::class, $in, $version)();
    }
}
