<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Product\Event\ProductProductVariantRemoved;
use Ecommerce\Catalog\Aggregate\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\RemoveProductVariant as CommandRemoveProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: RemoveProductVariant
 *
 * Operation: remove ProductVariant (many)
 */
class RemoveProductVariant extends EcommerceContext
{
    /**
     * Removes a ProductVariant from the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveProductVariant $removeProductVariant */
            $removeProductVariant = $this->payload();

            $query = $this->handle(QueryProduct::class, identifier: $removeProductVariant->productIdentifier);
            /** @var Product $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);

            $items = $handler->getData()->getProductVariant();
            $found = false;
            foreach ($items as $item) {
                if ($item->getIdentifier() === $removeProductVariant->productVariantIdentifier) {
                    $handler->removeProductVariant($item->getId());
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("ProductVariant not found: " . $removeProductVariant->productVariantIdentifier);
            }

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['productIdentifier' => $removeProductVariant->productIdentifier]);

            $event = $this->handle(
                ProductProductVariantRemoved::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productVariantIdentifier: $removeProductVariant->productVariantIdentifier,
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
