<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\Action\BuildAddProductImageData;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidateAddProductImage;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Product\Event\ProductProductImageAdded;
use Ecommerce\Catalog\Aggregate\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\AddProductImage as CommandAddProductImage;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIdentifier as QueryProduct;

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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddProductImage $addProductImage */
            $addProductImage = $this->payload();

            $query = $this->handle(QueryProduct::class, identifier: $addProductImage->productIdentifier);
            /** @var Product $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);

            $this->handle(ValidateAddProductImage::class)($addProductImage);
            $entityData = $this->handle(BuildAddProductImageData::class)($addProductImage);

            $handler->addProductImage($entityData);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $productImage = $handler->getData()->getProductImage();
            $this->result()->setData([
                'productIdentifier' => $addProductImage->productIdentifier,
                'productImageId' => $productImage[array_key_last($productImage)]->getId(),
            ]);

            $productImageForEvent = $handler->getData()->getProductImage();
            $addedEntity = !empty($productImageForEvent) ? $productImageForEvent[array_key_last($productImageForEvent)] : null;

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
