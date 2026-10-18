<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildAddProductVariantPriceData;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidateAddProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Product\Event\ProductProductVariantPriceAdded;
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
use Ecommerce\Catalog\Model\Product\Command\AddProductVariantPrice as CommandAddProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: AddProductVariantPrice
 *
 * Operation: add ProductVariantPrice (many)
 */
class AddProductVariantPrice extends EcommerceContext
{
    /**
     * Adds the ProductVariantPrice entity.
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
            /** @var CommandAddProductVariantPrice $addProductVariantPrice */
            $addProductVariantPrice = $this->payload();

            $this->handle(ValidateAddProductVariantPrice::class)($addProductVariantPrice);

            $query = $this->handle(QueryProduct::class, identifier: $addProductVariantPrice->productIdentifier);
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $addProductVariantPrice->productIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildAddProductVariantPriceData::class)($addProductVariantPrice);

            $miss = $handler->addProductVariantPrice($addProductVariantPrice->productVariantIdentifier, $entityData);
            if ($miss !== null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productVariantIdentifier' => $addProductVariantPrice->productVariantIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $productVariant = $handler->getData()->getProductVariant();
            $productVariantPriceIdentifier = null;
            foreach ($productVariant as $item) {
                if ($item->getIdentifier() === $addProductVariantPrice->productVariantIdentifier) {
                    $productVariantPriceCollection = $item->getProductVariantPrice();
                    $productVariantPriceIdentifier = !empty($productVariantPriceCollection)
                        ? $productVariantPriceCollection[array_key_last($productVariantPriceCollection)]->getId()
                        : null;
                    break;
                }
            }
            $this->result()->setData([
                '@type' => 'product',
                'productIdentifier' => $addProductVariantPrice->productIdentifier,
                'productVariantIdentifier' => $addProductVariantPrice->productVariantIdentifier,
                'productVariantPriceId' => $productVariantPriceIdentifier,
            ]);

            $productVariantPriceForEvent = [];
            foreach ($handler->getData()->getProductVariant() as $parentItem) {
                if ($parentItem->getIdentifier() === $addProductVariantPrice->productVariantIdentifier) {
                    $productVariantPriceForEvent = $parentItem->getProductVariantPrice();
                    break;
                }
            }
            $addedEntity = !empty($productVariantPriceForEvent)
                ? $productVariantPriceForEvent[array_key_last($productVariantPriceForEvent)]
                : null;

            $event = $this->handle(
                ProductProductVariantPriceAdded::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productVariantPriceId: $addedEntity?->getId(),
                region: $addedEntity?->getRegion(),
                price: $addedEntity?->getPrice(),
                currency: $addedEntity?->getCurrency(),
                validFrom: $addedEntity?->getValidFrom() !== null
                    ? DateTimeImmutable::createFromInterface($addedEntity?->getValidFrom())
                    : null,
                validUntil: $addedEntity?->getValidUntil() !== null
                    ? DateTimeImmutable::createFromInterface($addedEntity?->getValidUntil())
                    : null,
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
