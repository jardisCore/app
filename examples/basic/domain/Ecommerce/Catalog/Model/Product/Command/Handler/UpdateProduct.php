<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildUpdateProductData;
use Ecommerce\Catalog\Model\Product\Command\Handler\Action\BuildUpdateProductExpected;
use Ecommerce\Catalog\Model\Product\Command\Validation\UpdateProductValidatorRegistry;
use Ecommerce\Catalog\Model\Product\Event\ProductUpdated;
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
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Model\Product\Command\UpdateProduct as CommandUpdateProduct;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProduct;

/**
 * Command endpoint: UpdateProduct
 *
 * Operation: update Product (root)
 */
class UpdateProduct extends EcommerceContext
{
    /**
     * Updates the root Product entity.
     *
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        /** @var CommandUpdateProduct $updateProduct */
        $updateProduct = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateProductValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateProduct);
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
                tenant: $updateProduct->tenant,
                identifier: $updateProduct->productIdentifier
            );
            /** @var Product|null $handler */
            $handler = $this->handle(ProductRepository::class)->getProductByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['productIdentifier' => $updateProduct->productIdentifier]
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

            $entityData = $this->handle(BuildUpdateProductData::class)($updateProduct);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildUpdateProductExpected::class)($expected));
            }

            $handler->setProduct($entityData);

            $persistResult = $this->handle(ProductRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'product',
                'id' => $handler->getData()->getId(),
                'productIdentifier' => $updateProduct->productIdentifier,
            ]);

            $event = $this->handle(
                ProductUpdated::class,
                productIdentifier: $handler->getData()->getIdentifier(),
                tenantKey: $handler->getData()->getTenantKey(),
                sku: $handler->getData()->getSku(),
                productName: $handler->getData()->getName(),
                slug: $handler->getData()->getSlug(),
                description: $handler->getData()->getDescription(),
                shortDescription: $handler->getData()->getShortDescription(),
                price: $handler->getData()->getPrice(),
                compareAtPrice: $handler->getData()->getCompareAtPrice(),
                costPrice: $handler->getData()->getCostPrice(),
                currency: $handler->getData()->getCurrency(),
                weightGrams: $handler->getData()->getWeightGrams(),
                isActive: $handler->getData()->getIsActive(),
                isFeatured: $handler->getData()->getIsFeatured(),
                taxClass: $handler->getData()->getTaxClass()?->value
                    ?? throw new RuntimeException('taxClass is required'),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Product');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateProduct');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
