<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Service;

use Ecommerce\Catalog\Catalog;
use Ecommerce\Catalog\Model\Product\Command\UpdateProduct;
use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\UpdateProductInCatalog;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use Ecommerce\Sales\Sales;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;
use JardisSupport\Workflow\Data\Route;
use Throwable;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProductByIdentifier;

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
 * Service: CheckStockInCatalog
 */
final class CheckStockInCatalog extends EcommerceContext
{
    /**
     * Fuehrt den Cross-BC-Write auf die fremde BC Catalog ausschliesslich ueber
     * deren Aussentuer aus: Lesen ueber die Lese-Fassade ($bc->product()),
     * Schreiben ueber den Prozess ($bc->process()->updateProductInCatalog()).
     * Kein Aggregat-Direktzugriff, keine Kernel-Naht auf das fremde BC (G7).
     *
     * @throws Throwable
     */
    public function __invoke(ProcessContext $context): NodeResult
    {
        /** @var CrossBcServiceDemo $cmd */
        $cmd = $context->input();

        /** @var Catalog $catalog */
        $catalog = $this->handle(Catalog::class);

        // ACL, Schritt 1 — den fremden Ist-Stand ueber die Lese-Fassade des
        // Ziel-BC ziehen (kein Aggregat-Direktzugriff).
        /** @var DomainResponseInterface $read */
        $read = $catalog->product()->getProductByIdentifier(
            new QueryProductByIdentifier(tenant: $cmd->tenant, identifier: $cmd->productIdentifier)
        );
        if (!$read->isSuccess()) {
            return NodeResult::route(Route::OnFalse, ['productIdentifier' => $cmd->productIdentifier]);
        }

        // Flat payload: the product's fields sit directly in data (typed by
        // `@type`); a product that is not found leaves the payload empty.
        $data = $read->getData();
        if ($data === []) {
            return NodeResult::route(Route::OnFalse, ['productIdentifier' => $cmd->productIdentifier]);
        }

        $current = (object) $data;

        // ACL, Schritt 2 — die eigene Eingabe in das fremde Prozess-Input-DTO
        // uebersetzen (nur der Preis aendert sich, der Rest bleibt der
        // Ist-Stand des fremden BC).
        $update = new UpdateProductInCatalog(
            tenant: $cmd->tenant,
            user: $cmd->user,
            update: new UpdateProduct(
                tenant: $cmd->tenant,
                productIdentifier: $current->identifier,
                sku: $current->sku,
                productName: $current->productName,
                slug: $current->slug,
                description: $current->description,
                shortDescription: $current->shortDescription,
                price: number_format($cmd->newPrice, 2, '.', ''),
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
        $answer = [
            'productIdentifier' => $current->identifier,
            'newPrice'          => $cmd->newPrice,
        ];

        return $response->isSuccess() ? NodeResult::ok($answer) : NodeResult::route(Route::OnFalse, $answer);
    }
}
