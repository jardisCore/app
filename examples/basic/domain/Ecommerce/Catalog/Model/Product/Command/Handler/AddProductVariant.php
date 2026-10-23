<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildAddProductVariantData;
use Ecommerce\Catalog\Model\Product\Command\Validation\AddProductVariantValidatorRegistry;
use Ecommerce\Catalog\Model\Product\Event\ProductProductVariantAdded;
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
use Ecommerce\Catalog\Model\Product\Command\AddProductVariant as CommandAddProductVariant;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: AddProductVariant
 *
 * Operation: add ProductVariant (many)
 */
class AddProductVariant extends EcommerceContext
{
    /**
     * Adds the ProductVariant entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandAddProductVariant $addProductVariant */
        $addProductVariant = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(AddProductVariantValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($addProductVariant);
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
            $query = $this->handle(QueryProduct::class, identifier: $addProductVariant->productIdentifier);
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $addProductVariant->productIdentifier]
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

            $entityData = $this->handle(BuildAddProductVariantData::class)($addProductVariant);

            $handler->addProductVariant($entityData);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $productVariant = $handler->getData()->getProductVariant();
            $this->result()->setData([
                '@type' => 'product',
                'id' => $handler->getData()->getId(),
                'productIdentifier' => $addProductVariant->productIdentifier,
                'productVariantIdentifier' => $productVariant[array_key_last($productVariant)]->getIdentifier(),
            ]);

            $productVariantForEvent = $handler->getData()->getProductVariant();
            $addedEntity = !empty($productVariantForEvent)
                ? $productVariantForEvent[array_key_last($productVariantForEvent)]
                : null;

            $event = $this->handle(
                ProductProductVariantAdded::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productVariantIdentifier: $addedEntity?->getIdentifier(),
                sku: $addedEntity?->getSku(),
                variantName: $addedEntity?->getVariantName(),
                optionName: $addedEntity?->getOptionName(),
                optionValue: $addedEntity?->getOptionValue(),
                priceModifier: $addedEntity?->getPriceModifier(),
                stockQuantity: $addedEntity?->getStockQuantity(),
                lowStockThreshold: $addedEntity?->getLowStockThreshold(),
                weightGrams: $addedEntity?->getWeightGrams(),
                isAvailable: $addedEntity?->getIsAvailable(),
                sortOrder: $addedEntity?->getSortOrder(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Product');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'AddProductVariant');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
