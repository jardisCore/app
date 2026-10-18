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
