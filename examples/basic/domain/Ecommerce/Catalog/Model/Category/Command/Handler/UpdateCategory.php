<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category;
use Ecommerce\Catalog\Model\Category\Command\Handler\Action\BuildUpdateCategoryData;
use Ecommerce\Catalog\Model\Category\Command\Handler\Action\BuildUpdateCategoryExpected;
use Ecommerce\Catalog\Model\Category\Command\Validation\UpdateCategoryValidatorRegistry;
use Ecommerce\Catalog\Model\Category\Event\CategoryUpdated;
use Ecommerce\Catalog\Model\Category\Repository\CategoryRepository;
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
use Ecommerce\Catalog\Model\Category\Command\UpdateCategory as CommandUpdateCategory;
use Ecommerce\Catalog\Model\Category\Query\CategoryByIdentifier as QueryCategory;

/**
 * Command endpoint: UpdateCategory
 *
 * Operation: update Category (root)
 */
class UpdateCategory extends EcommerceContext
{
    /**
     * Updates the root Category entity.
     *
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        /** @var CommandUpdateCategory $updateCategory */
        $updateCategory = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateCategoryValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateCategory);
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
                QueryCategory::class,
                tenant: $updateCategory->tenant,
                identifier: $updateCategory->categoryIdentifier
            );
            /** @var Category|null $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryIdentifier' => $updateCategory->categoryIdentifier]
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

            $entityData = $this->handle(BuildUpdateCategoryData::class)($updateCategory);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildUpdateCategoryExpected::class)($expected));
            }

            $handler->setCategory($entityData);

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'category',
                'id' => $handler->getData()->getId(),
                'categoryIdentifier' => $updateCategory->categoryIdentifier,
            ]);

            $event = $this->handle(
                CategoryUpdated::class,
                categoryIdentifier: $handler->getData()->getIdentifier(),
                tenantKey: $handler->getData()->getTenantKey(),
                slug: $handler->getData()->getSlug(),
                parentIdentifier: $handler->getData()->getParentIdentifier(),
                categoryName: $handler->getData()->getName(),
                description: $handler->getData()->getDescription(),
                metaTitle: $handler->getData()->getMetaTitle(),
                metaDescription: $handler->getData()->getMetaDescription(),
                isActive: $handler->getData()->getIsActive(),
                isVisible: $handler->getData()->getIsVisible(),
                sortOrder: $handler->getData()->getSortOrder(),
                productCount: $handler->getData()->getProductCount(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Category');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateCategory');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
