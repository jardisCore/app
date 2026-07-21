<?php

declare(strict_types=1);

namespace Ecommerce\Service;

use Ecommerce\Catalog\Aggregate\Product\Command\UpdateProduct;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIdentifier as QueryProductByIdentifier;
use Ecommerce\Catalog\Aggregate\Product\Query\Response\ProductResponse;
use Ecommerce\Catalog\Catalog;
use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\UpdateProductInCatalog;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use Ecommerce\Sales\Sales;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Workflow\WorkflowResult;
use RuntimeException;
use Throwable;

/**
 * Same-Domain-Variante (D3): schreibt in die fremde BC Catalog im eigenen
 * Domain Ecommerce ueber deren Prozess UpdateProductInCatalog (G7), nicht
 * direkt ueber das Aggregat. Der Domain-Service uebersetzt die eigene
 * Eingabe (productIdentifier + newPrice) in das fremde Prozess-Input-DTO
 * und mappt die Antwort ins eigene Vokabular zurueck (ACL).
 *
 * Cross-BC-Ziel: Ecommerce.Catalog.UpdateProductInCatalog::updateProductInCatalog (UpdateProductInCatalog).
 *
 * ACL-Hinweis: Antwort in eigenes Vokabular uebersetzen, fremdes DTO
 * nicht unveraendert durchreichen.
 */
final class CheckStockInCatalog extends EcommerceContext
{
    /**
     * Fuehrt den Cross-BC-Write auf die fremde BC Catalog ausschliesslich ueber
     * deren Aussentuer aus: Lesen ueber die Lese-Fassade ($bc->product()),
     * Schreiben ueber den Prozess ($bc->process()->updateProductInCatalog()).
     * Kein Aggregat-Direktzugriff, keine Kernel-Naht auf das fremde BC (G7).
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws Throwable
     */
    public function __invoke(WorkflowContextInterface $context): array
    {
        /** @var CrossBcServiceDemo $cmd */
        $cmd = $this->payload();

        /** @var Catalog $catalog */
        $catalog = $this->handle(Catalog::class);

        // ACL, Schritt 1 — den fremden Ist-Stand ueber die Lese-Fassade des
        // Ziel-BC ziehen (kein Aggregat-Direktzugriff).
        /** @var DomainResponseInterface $read */
        $read = $catalog->product()->getProductByIdentifier(
            new QueryProductByIdentifier(identifier: $cmd->productIdentifier)
        );
        if (!$read->isSuccess()) {
            return [
                'status' => WorkflowResult::ON_FAIL,
                'data'   => ['productIdentifier' => $cmd->productIdentifier],
            ];
        }

        $data = $read->getData();
        /** @var array<string, ProductResponse> $handlerData */
        $handlerData = $data['GetProductByIdentifierHandler'] ?? [];
        $current = $handlerData['product'] ?? null;
        if (!$current instanceof ProductResponse) {
            return [
                'status' => WorkflowResult::ON_FAIL,
                'data'   => ['productIdentifier' => $cmd->productIdentifier],
            ];
        }

        // ACL, Schritt 2 — die eigene Eingabe in das fremde Prozess-Input-DTO
        // uebersetzen (nur der Preis aendert sich, der Rest bleibt der
        // Ist-Stand des fremden BC).
        $update = new UpdateProductInCatalog(
            new UpdateProduct(
                productIdentifier: $current->identifier,
                sku: $current->sku,
                productName: $current->productName,
                slug: $current->slug,
                description: $current->description,
                shortDescription: $current->shortDescription,
                price: $cmd->newPrice,
                compareAtPrice: $current->compareAtPrice,
                costPrice: $current->costPrice,
                currency: $current->currency,
                weightGrams: $current->weightGrams,
                isActive: $current->isActive,
                isFeatured: $current->isFeatured,
                taxClass: $current->taxClass,
            )
        );

        // ACL, Schritt 3 — den Write ueber den process() des fremden BC
        // ausfuehren.
        /** @var DomainResponseInterface $response */
        $response = $catalog->process()->updateProductInCatalog($update);

        // ACL, Schritt 4 — die Antwort ins eigene Vokabular mappen; das fremde
        // DTO wird nicht unveraendert durchgereicht.
        return [
            'status' => $response->isSuccess() ? WorkflowResult::ON_SUCCESS : WorkflowResult::ON_FAIL,
            'data'   => [
                'productIdentifier' => $current->identifier,
                'newPrice'          => $cmd->newPrice,
            ],
        ];
    }
}
