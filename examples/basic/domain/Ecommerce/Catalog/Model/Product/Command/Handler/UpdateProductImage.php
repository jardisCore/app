<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildUpdateProductImageData;
use Ecommerce\Catalog\Model\Product\Command\Validation\UpdateProductImageValidatorRegistry;
use Ecommerce\Catalog\Model\Product\Event\ProductProductImageUpdated;
use Ecommerce\Catalog\Model\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandUpdateProductImage $updateProductImage */
        $updateProductImage = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateProductImageValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateProductImage);
        if ($findings->fields !== []) {
            foreach ($findings->errors as $error) {
                $this->result()->addError($error);
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))(
                    ResponseStatus::ValidationError,
                    ($this->handle(ValidationPayload::class))('validation', $findings->fields),
                    null,
                    ''
                )
            );

            return ($this->transformer())($this->result(), ResponseStatus::ValidationError);
        }
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(QueryProduct::class, identifier: $updateProductImage->productIdentifier);
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $updateProductImage->productIdentifier]
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
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productImageId' => $updateProductImage->productImageId]
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Product');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateProductImage');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
