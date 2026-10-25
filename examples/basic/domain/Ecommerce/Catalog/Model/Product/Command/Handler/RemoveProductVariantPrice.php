<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Event\ProductProductVariantPriceRemoved;
use Ecommerce\Catalog\Model\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use Throwable;
use Ecommerce\Catalog\Model\Product\Command\RemoveProductVariantPrice as CommandRemoveProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

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
        /** @var CommandRemoveProductVariantPrice $removeProductVariantPrice */
        $removeProductVariantPrice = $this->payload();
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(
                QueryProduct::class,
                tenant: $removeProductVariantPrice->tenant,
                identifier: $removeProductVariantPrice->productIdentifier
            );
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $removeProductVariantPrice->productIdentifier]
                        ),
                        null,
                        ''
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $parent = null;
            foreach ($handler->getData()->getProductVariant() as $candidate) {
                if ($candidate->getIdentifier() === $removeProductVariantPrice->productVariantIdentifier) {
                    $parent = $candidate;
                    break;
                }
            }
            if ($parent === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productVariantIdentifier' => $removeProductVariantPrice->productVariantIdentifier]
                        ),
                        null,
                        ''
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
                if ($item->getId() === $removeProductVariantPrice->productVariantPriceId) {
                    $handler->removeProductVariantPrice($item->getId());
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productVariantPriceId' => $removeProductVariantPrice->productVariantPriceId]
                        ),
                        null,
                        ''
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
                'id' => $handler->getData()->getId(),
                'productIdentifier' => $removeProductVariantPrice->productIdentifier,
            ]);

            $event = $this->handle(
                ProductProductVariantPriceRemoved::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productVariantPriceId: $removeProductVariantPrice->productVariantPriceId,
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Product');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'RemoveProductVariantPrice');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
