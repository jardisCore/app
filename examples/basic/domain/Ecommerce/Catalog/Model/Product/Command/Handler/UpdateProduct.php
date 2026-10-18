<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildUpdateProductData;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildUpdateProductExpected;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidateUpdateProduct;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Product\Event\ProductUpdated;
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
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Model\Product\Command\UpdateProduct as CommandUpdateProduct;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

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
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        try {
            /** @var CommandUpdateProduct $updateProduct */
            $updateProduct = $this->payload();

            $this->handle(ValidateUpdateProduct::class)($updateProduct);

            $query = $this->handle(QueryProduct::class, identifier: $updateProduct->productIdentifier);
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $updateProduct->productIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildUpdateProductData::class)($updateProduct);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildUpdateProductExpected::class)($expected));
            }

            $handler->setProduct($entityData);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'product',
                'productIdentifier' => $updateProduct->productIdentifier,
            ]);

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
                taxClass: $handler->getData()->getTaxClass()?->value
                    ?? throw new RuntimeException('taxClass is required'),
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
