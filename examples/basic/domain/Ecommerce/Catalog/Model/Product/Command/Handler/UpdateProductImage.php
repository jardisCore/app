<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildUpdateProductImageData;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidateUpdateProductImage;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Product\Event\ProductProductImageUpdated;
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
use Ecommerce\Catalog\Model\Product\Command\UpdateProductImage as CommandUpdateProductImage;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: UpdateProductImage
 *
 * Operation: update ProductImage (many)
 */
class UpdateProductImage extends EcommerceContext
{
    /**
     * Updates a ProductImage in the collection.
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
            /** @var CommandUpdateProductImage $updateProductImage */
            $updateProductImage = $this->payload();

            $this->handle(ValidateUpdateProductImage::class)($updateProductImage);

            $query = $this->handle(QueryProduct::class, identifier: $updateProductImage->productIdentifier);
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $updateProductImage->productIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildUpdateProductImageData::class)($updateProductImage);

            $items = $handler->getData()->getProductImage();
            $found = false;
            foreach ($items as $item) {
                if ($item->getId() === $updateProductImage->productImageId) {
                    $handler->updateProductImage($item->getId(), $entityData);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productImageId' => $updateProductImage->productImageId]
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
                'productIdentifier' => $updateProductImage->productIdentifier,
                'productImageId' => $updateProductImage->productImageId,
            ]);

            $event = $this->handle(
                ProductProductImageUpdated::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productImageId: $updateProductImage->productImageId,
                url: $updateProductImage->url,
                altText: $updateProductImage->altText,
                mimeType: $updateProductImage->mimeType,
                fileSize: $updateProductImage->fileSize,
                width: $updateProductImage->width,
                height: $updateProductImage->height,
                sortOrder: $updateProductImage->sortOrder,
                isPrimary: $updateProductImage->isPrimary,
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
