<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildAddProductVariantData;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidateAddProductVariant;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Product\Event\ProductProductVariantAdded;
use Ecommerce\Catalog\Model\Product\Repository\ConcurrencyConflictException;
use Ecommerce\Catalog\Model\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\NotFoundPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
use Throwable;
use Ecommerce\Catalog\Model\Product\Command\AddProductVariant as CommandAddProductVariant;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddProductVariant $addProductVariant */
            $addProductVariant = $this->payload();

            $this->handle(ValidateAddProductVariant::class)($addProductVariant);

            $query = $this->handle(QueryProduct::class, identifier: $addProductVariant->productIdentifier);
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $addProductVariant->productIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildAddProductVariantData::class)($addProductVariant);

            $handler->addProductVariant($entityData);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $productVariant = $handler->getData()->getProductVariant();
            $this->result()->setData([
                '@type' => 'product',
                'productIdentifier' => $addProductVariant->productIdentifier,
                'productVariantIdentifier' => $productVariant[array_key_last($productVariant)]->getIdentifier(),
            ]);

            $productVariantForEvent = $handler->getData()->getProductVariant();
            $addedEntity = !empty($productVariantForEvent)
                ? $productVariantForEvent[array_key_last($productVariantForEvent)]
                : null;

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

            return ($this->transformer())($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());
            $this->result()->setData(
                $this->failureData(($this->handle(ValidationPayload::class))('validation', $e->fields))
            );

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::ValidationError
            );
        } catch (ConcurrencyConflictException $e) {
            $this->result()->setData(
                $this->failureData(
                    ($this->handle(ConcurrencyConflictPayload::class))(
                        'concurrencyConflict',
                        $e->getReason(),
                        $e->getAggregate(),
                        $e->getContext(),
                        $e->getViolated()
                    )
                )
            );

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::Conflict
            );
        } catch (TransientConflictException $transient) {
            throw $transient;
        } catch (Throwable $e) {
            $this->result()->addError($e->getMessage());

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::InternalError
            );
        }
    }
}
