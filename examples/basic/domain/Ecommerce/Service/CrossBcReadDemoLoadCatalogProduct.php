<?php

declare(strict_types=1);

namespace Ecommerce\Service;

use Ecommerce\Catalog\Catalog;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcReadDemo\Command\CrossBcReadDemo;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;
use Throwable;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProductByIdentifier;

/**
 * Liest ein Product der fremden BC Catalog ueber deren Lese-Fassade
 * ($catalog->product()->getProductByIdentifier()). Der Service uebersetzt die
 * Antwort ins eigene Vokabular (ACL).
 *
 * Cross-BC-Ziel: Ecommerce.Catalog.Product::getProductByIdentifier.
 *
 * ACL-Hinweis: Antwort in eigenes Vokabular uebersetzen, fremdes DTO
 * nicht unveraendert durchreichen.
 *
 * Antwort: ein NodeResult (jardissupport/workflow). NodeResult::ok() bei Erfolg,
 * NodeResult::route(Route::OnFalse|OnTimeout|OnSkip, ...) fuer einen Zweig,
 * NodeResult::reject(<4xx-Status>, <messageKey>, <context>) fuer eine Ablehnung.
 * Die Fehlerform baut das Paket aus dem Kontext: 400 'fields', 409 'reason' /
 * 'aggregate' / 'violated', 422 'rule'; weitere Schluessel bleiben Kontext der Form.
 * Ein 5xx-Fall ist eine Exception, kein reject().
 */
final class CrossBcReadDemoLoadCatalogProduct extends EcommerceContext
{
    /**
     * @throws Throwable
     */
    public function __invoke(ProcessContext $context): NodeResult
    {
        /** @var CrossBcReadDemo $cmd */
        $cmd = $context->input();

        /** @var Catalog $catalog */
        $catalog = $this->handle(Catalog::class);

        // Cross-BC-LESEN ausschliesslich ueber die Lese-Fassade des fremden BC
        // ($catalog->product()) — kein Aggregat-Direktzugriff, keine Kernel-Naht
        // auf das fremde BC. Die Action importiert nichts aus Catalog; dieser
        // Service ist die einzige Stelle, die den fremden BC kennt.
        /** @var DomainResponseInterface $read */
        $read = $catalog->product()->getProductByIdentifier(
            new QueryProductByIdentifier(identifier: $cmd->productIdentifier)
        );

        // ACL: die Antwort ins eigene Vokabular mappen, das fremde DTO nicht
        // durchreichen.
        $data = $read->getData();
        if (!$read->isSuccess() || $data === []) {
            // A foreign aggregate that does not exist answers 404 in the own
            // vocabulary (failure form 404: context).
            return NodeResult::reject(
                ResponseStatus::NotFound,
                'product.notFound',
                ['context' => $cmd->productIdentifier]
            );
        }

        return NodeResult::ok(['productIdentifier' => $cmd->productIdentifier]);
    }
}
