<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Product\Event\ProductProductVariantPriceRemoved;
use Ecommerce\Catalog\Aggregate\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\RemoveProductVariantPrice as CommandRemoveProductVariantPrice;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: RemoveProductVariantPrice
 *
 * Operation: remove ProductVariantPrice (many)
 */
class RemoveProductVariantPrice extends EcommerceContext
{
    /**
     * Removes a ProductVariantPrice from the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveProductVariantPrice $removeProductVariantPrice */
            $removeProductVariantPrice = $this->payload();

            $query = $this->handle(QueryProduct::class, identifier: $removeProductVariantPrice->productIdentifier);
            /** @var Product $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);

            $handler->removeProductVariantPrice($removeProductVariantPrice->productVariantPriceId);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['productIdentifier' => $removeProductVariantPrice->productIdentifier]);

            $event = $this->handle(
                ProductProductVariantPriceRemoved::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productVariantPriceId: $removeProductVariantPrice->productVariantPriceId,
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());

            return $this->handle(DomainResponseTransformer::class)->transform(
                $this->result(),
                ResponseStatus::ValidationError
            );
        } catch (\Throwable $e) {
            $this->result()->addError($e->getMessage());

            return $this->handle(DomainResponseTransformer::class)->transform(
                $this->result(),
                ResponseStatus::InternalError
            );
        }
    }
}
