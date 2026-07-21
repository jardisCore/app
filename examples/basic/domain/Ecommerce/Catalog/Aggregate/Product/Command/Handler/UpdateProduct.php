<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\Action\BuildUpdateProductData;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidateUpdateProduct;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Product\Event\ProductUpdated;
use Ecommerce\Catalog\Aggregate\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\UpdateProduct as CommandUpdateProduct;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: UpdateProduct
 *
 * Operation: update Product (root)
 */
class UpdateProduct extends EcommerceContext
{
    /**
     * Updates the root Product entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandUpdateProduct $updateProduct */
            $updateProduct = $this->payload();

            $query = $this->handle(QueryProduct::class, identifier: $updateProduct->productIdentifier);
            /** @var Product $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);

            $this->handle(ValidateUpdateProduct::class)($updateProduct);
            $entityData = $this->handle(BuildUpdateProductData::class)($updateProduct);

            $handler->setProduct($entityData);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['productIdentifier' => $updateProduct->productIdentifier]);

            $event = $this->handle(
                ProductUpdated::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                sku: $handler->getData()->getSku(),
                productName: $handler->getData()->getName(),
                slug: $handler->getData()->getSlug(),
                description: $handler->getData()->getDescription(),
                shortDescription: $handler->getData()->getShortDescription(),
                price: $handler->getData()->getPrice(),
                compareAtPrice: $handler->getData()->getCompareAtPrice(),
                costPrice: $handler->getData()->getCostPrice(),
                currency: $handler->getData()->getCurrency(),
                weightGrams: $handler->getData()->getWeightGrams(),
                isActive: $handler->getData()->getIsActive(),
                isFeatured: $handler->getData()->getIsFeatured(),
                taxClass: $handler->getData()->getTaxClass()?->value ?? throw new RuntimeException('taxClass is required'),
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
