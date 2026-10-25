<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildUpdateProductVariantPriceData;
use Ecommerce\Catalog\Model\Product\Command\Validation\UpdateProductVariantPriceValidatorRegistry;
use Ecommerce\Catalog\Model\Product\Event\ProductProductVariantPriceUpdated;
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
use Ecommerce\Catalog\Model\Product\Command\UpdateProductVariantPrice as CommandUpdateProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: UpdateProductVariantPrice
 *
 * Operation: update ProductVariantPrice (many)
 */
class UpdateProductVariantPrice extends EcommerceContext
{
    /**
     * Updates a ProductVariantPrice in the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandUpdateProductVariantPrice $updateProductVariantPrice */
        $updateProductVariantPrice = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateProductVariantPriceValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateProductVariantPrice);
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
            $query = $this->handle(
                QueryProduct::class,
                tenant: $updateProductVariantPrice->tenant,
                identifier: $updateProductVariantPrice->productIdentifier
            );
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $updateProductVariantPrice->productIdentifier]
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

            $entityData = $this->handle(BuildUpdateProductVariantPriceData::class)($updateProductVariantPrice);

            $parent = null;
            foreach ($handler->getData()->getProductVariant() as $candidate) {
                if ($candidate->getIdentifier() === $updateProductVariantPrice->productVariantIdentifier) {
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
                            ['productVariantIdentifier' => $updateProductVariantPrice->productVariantIdentifier]
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
                if ($item->getId() === $updateProductVariantPrice->productVariantPriceId) {
                    $handler->updateProductVariantPrice($item->getId(), $entityData);
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
                            ['productVariantPriceId' => $updateProductVariantPrice->productVariantPriceId]
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
                'productIdentifier' => $updateProductVariantPrice->productIdentifier,
                'productVariantIdentifier' => $updateProductVariantPrice->productVariantIdentifier,
                'productVariantPriceId' => $updateProductVariantPrice->productVariantPriceId,
            ]);

            $event = $this->handle(
                ProductProductVariantPriceUpdated::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                productVariantPriceId: $updateProductVariantPrice->productVariantPriceId,
                region: $updateProductVariantPrice->region,
                price: $updateProductVariantPrice->price,
                currency: $updateProductVariantPrice->currency,
                validFrom: $updateProductVariantPrice->validFrom,
                validUntil: $updateProductVariantPrice->validUntil,
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Product');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateProductVariantPrice');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
