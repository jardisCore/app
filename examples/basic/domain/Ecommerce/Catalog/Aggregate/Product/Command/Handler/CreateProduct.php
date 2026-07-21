<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\Action\HydrateCreateProductEntities;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidateCreateProduct;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Product\Event\ProductCreated;
use Ecommerce\Catalog\Aggregate\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\Product as CommandProduct;

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
     * @throws Exception
     * @throws Throwable
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

            $this->result()->setData(['productIdentifier' => $handler->getData()->getIdentifier()]);

            $event = $this->handle(ProductCreated::class, productIdentifier: $handler->getData()->getIdentifier(), occurredAt: new DateTimeImmutable());
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::Created);
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
