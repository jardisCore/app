<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildAddProductVariantPriceData;
use Ecommerce\Catalog\Model\Product\Command\Validation\AddProductVariantPriceValidatorRegistry;
use Ecommerce\Catalog\Model\Product\Event\ProductProductVariantPriceAdded;
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
use Ecommerce\Catalog\Model\Product\Command\AddProductVariantPrice as CommandAddProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: AddProductVariantPrice
 *
 * Operation: add ProductVariantPrice (many)
 */
class AddProductVariantPrice extends EcommerceContext
{
    /**
     * Adds the ProductVariantPrice entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandAddProductVariantPrice $addProductVariantPrice */
        $addProductVariantPrice = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(AddProductVariantPriceValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($addProductVariantPrice);
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
            $query = $this->handle(QueryProduct::class, identifier: $addProductVariantPrice->productIdentifier);
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $addProductVariantPrice->productIdentifier]
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

            $entityData = $this->handle(BuildAddProductVariantPriceData::class)($addProductVariantPrice);

            $miss = $handler->addProductVariantPrice($addProductVariantPrice->productVariantIdentifier, $entityData);
            if ($miss !== null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productVariantIdentifier' => $addProductVariantPrice->productVariantIdentifier]
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

            $productVariant = $handler->getData()->getProductVariant();
            $productVariantPriceIdentifier = null;
            foreach ($productVariant as $item) {
                if ($item->getIdentifier() === $addProductVariantPrice->productVariantIdentifier) {
                    $productVariantPriceCollection = $item->getProductVariantPrice();
                    $productVariantPriceIdentifier = !empty($productVariantPriceCollection)
                        ? $productVariantPriceCollection[array_key_last($productVariantPriceCollection)]->getId()
                        : null;
                    break;
                }
            }
            $this->result()->setData([
                '@type' => 'product',
                'id' => $handler->getData()->getId(),
                'productIdentifier' => $addProductVariantPrice->productIdentifier,
                'productVariantIdentifier' => $addProductVariantPrice->productVariantIdentifier,
                'productVariantPriceId' => $productVariantPriceIdentifier,
            ]);

            $productVariantPriceForEvent = [];
            foreach ($handler->getData()->getProductVariant() as $parentItem) {
                if ($parentItem->getIdentifier() === $addProductVariantPrice->productVariantIdentifier) {
                    $productVariantPriceForEvent = $parentItem->getProductVariantPrice();
                    break;
                }
            }
            $addedEntity = !empty($productVariantPriceForEvent)
                ? $productVariantPriceForEvent[array_key_last($productVariantPriceForEvent)]
                : null;

            $event = $this->handle(
                ProductProductVariantPriceAdded::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productVariantPriceId: $addedEntity?->getId(),
                region: $addedEntity?->getRegion(),
                price: $addedEntity?->getPrice(),
                currency: $addedEntity?->getCurrency(),
                validFrom: $addedEntity?->getValidFrom() !== null
                    ? DateTimeImmutable::createFromInterface($addedEntity?->getValidFrom())
                    : null,
                validUntil: $addedEntity?->getValidUntil() !== null
                    ? DateTimeImmutable::createFromInterface($addedEntity?->getValidUntil())
                    : null,
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Product');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'AddProductVariantPrice');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
