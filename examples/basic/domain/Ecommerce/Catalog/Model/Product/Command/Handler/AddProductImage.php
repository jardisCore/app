<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildAddProductImageData;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidateAddProductImage;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Product\Event\ProductProductImageAdded;
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
use Ecommerce\Catalog\Model\Product\Command\AddProductImage as CommandAddProductImage;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: AddProductImage
 *
 * Operation: add ProductImage (many)
 */
class AddProductImage extends EcommerceContext
{
    /**
     * Adds the ProductImage entity.
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
            /** @var CommandAddProductImage $addProductImage */
            $addProductImage = $this->payload();

            $this->handle(ValidateAddProductImage::class)($addProductImage);

            $query = $this->handle(QueryProduct::class, identifier: $addProductImage->productIdentifier);
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $addProductImage->productIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildAddProductImageData::class)($addProductImage);

            $handler->addProductImage($entityData);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $productImage = $handler->getData()->getProductImage();
            $this->result()->setData([
                '@type' => 'product',
                'productIdentifier' => $addProductImage->productIdentifier,
                'productImageId' => $productImage[array_key_last($productImage)]->getId(),
            ]);

            $productImageForEvent = $handler->getData()->getProductImage();
            $addedEntity = !empty($productImageForEvent)
                ? $productImageForEvent[array_key_last($productImageForEvent)]
                : null;

            $event = $this->handle(
                ProductProductImageAdded::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productImageId: $addedEntity?->getId(),
                url: $addedEntity?->getUrl(),
                altText: $addedEntity?->getAltText(),
                mimeType: $addedEntity?->getMimeType(),
                fileSize: $addedEntity?->getFileSize(),
                width: $addedEntity?->getWidth(),
                height: $addedEntity?->getHeight(),
                sortOrder: $addedEntity?->getSortOrder(),
                isPrimary: $addedEntity?->getIsPrimary(),
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
