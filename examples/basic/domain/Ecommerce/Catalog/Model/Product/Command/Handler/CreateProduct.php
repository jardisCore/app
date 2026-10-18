<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\HydrateCreateProductEntities;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidateCreateProduct;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Product\Event\ProductCreated;
use Ecommerce\Catalog\Model\Product\Repository\ConcurrencyConflictException;
use Ecommerce\Catalog\Model\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
use Throwable;
use Ecommerce\Catalog\Model\Product\Command\Product as CommandProduct;

/**
 * Command endpoint: CreateProduct
 *
 * Creates a new Product aggregate
 */
class CreateProduct extends EcommerceContext
{
    /**
     * Creates a new Product.
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
            /** @var CommandProduct $product */
            $product = $this->payload();

            /** @var Product $handler */
            $handler = $this->handle(ProductRepository::class)->createNew();
            $this->handle(ValidateCreateProduct::class)($product);
            $this->handle(HydrateCreateProductEntities::class)($handler, $product);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'product',
                'productIdentifier' => $handler->getData()->getIdentifier(),
                'productVariantIdentifiers' => array_map(
                    fn($e) => $e->getIdentifier(),
                    $handler->getData()->getProductVariant()
                ),
            ]);

            $event = $this->handle(
                ProductCreated::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result(), ResponseStatus::Created);
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
