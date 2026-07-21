<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\Action\BuildAddProductVariantData;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidateAddProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Product\Event\ProductProductVariantAdded;
use Ecommerce\Catalog\Aggregate\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\AddProductVariant as CommandAddProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: AddProductVariant
 *
 * Operation: add ProductVariant (many)
 */
class AddProductVariant extends EcommerceContext
{
    /**
     * Adds the ProductVariant entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddProductVariant $addProductVariant */
            $addProductVariant = $this->payload();

            $query = $this->handle(QueryProduct::class, identifier: $addProductVariant->productIdentifier);
            /** @var Product $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);

            $this->handle(ValidateAddProductVariant::class)($addProductVariant);
            $entityData = $this->handle(BuildAddProductVariantData::class)($addProductVariant);

            $handler->addProductVariant($entityData);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $productVariant = $handler->getData()->getProductVariant();
            $this->result()->setData([
                'productIdentifier' => $addProductVariant->productIdentifier,
                'productVariantIdentifier' => $productVariant[array_key_last($productVariant)]->getIdentifier(),
            ]);

            $productVariantForEvent = $handler->getData()->getProductVariant();
            $addedEntity = !empty($productVariantForEvent) ? $productVariantForEvent[array_key_last($productVariantForEvent)] : null;

            $event = $this->handle(
                ProductProductVariantAdded::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productVariantIdentifier: $addedEntity?->getIdentifier(),
                sku: $addedEntity?->getSku(),
                variantName: $addedEntity?->getVariantName(),
                optionName: $addedEntity?->getOptionName(),
                optionValue: $addedEntity?->getOptionValue(),
                priceModifier: $addedEntity?->getPriceModifier(),
                stockQuantity: $addedEntity?->getStockQuantity(),
                lowStockThreshold: $addedEntity?->getLowStockThreshold(),
                weightGrams: $addedEntity?->getWeightGrams(),
                isAvailable: $addedEntity?->getIsAvailable(),
                sortOrder: $addedEntity?->getSortOrder(),
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
