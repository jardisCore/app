<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildUpdateProductVariantPriceData;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidateUpdateProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Product\Event\ProductProductVariantPriceUpdated;
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
use Ecommerce\Catalog\Model\Product\Command\UpdateProductVariantPrice as CommandUpdateProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: UpdateProductVariantPrice
 *
 * Operation: update ProductVariantPrice (many)
 */
class UpdateProductVariantPrice extends EcommerceContext
{
    /**
     * Updates a ProductVariantPrice in the collection.
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
            /** @var CommandUpdateProductVariantPrice $updateProductVariantPrice */
            $updateProductVariantPrice = $this->payload();

            $this->handle(ValidateUpdateProductVariantPrice::class)($updateProductVariantPrice);

            $query = $this->handle(QueryProduct::class, identifier: $updateProductVariantPrice->productIdentifier);
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $updateProductVariantPrice->productIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildUpdateProductVariantPriceData::class)($updateProductVariantPrice);

            $parent = null;
            foreach ($handler->getData()->getProductVariant() as $candidate) {
                if ($candidate->getIdentifier() === $updateProductVariantPrice->productVariantIdentifier) {
                    $parent = $candidate;
                    break;
                }
            }
            if ($parent === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productVariantIdentifier' => $updateProductVariantPrice->productVariantIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }
            $items = $parent->getProductVariantPrice();
            $found = false;
            foreach ($items as $item) {
                if ($item->getId() === $updateProductVariantPrice->productVariantPriceId) {
                    $handler->updateProductVariantPrice($item->getId(), $entityData);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productVariantPriceId' => $updateProductVariantPrice->productVariantPriceId]
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

            $this->result()->setData([
                '@type' => 'product',
                'productIdentifier' => $updateProductVariantPrice->productIdentifier,
                'productVariantIdentifier' => $updateProductVariantPrice->productVariantIdentifier,
                'productVariantPriceId' => $updateProductVariantPrice->productVariantPriceId,
            ]);

            $event = $this->handle(
                ProductProductVariantPriceUpdated::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productVariantPriceId: $updateProductVariantPrice->productVariantPriceId,
                region: $updateProductVariantPrice->region,
                price: $updateProductVariantPrice->price,
                currency: $updateProductVariantPrice->currency,
                validFrom: $updateProductVariantPrice->validFrom,
                validUntil: $updateProductVariantPrice->validUntil,
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
