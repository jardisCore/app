<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category;
use Ecommerce\Catalog\Model\Category\Command\Handler\Action\BuildUpdateCategoryData;
use Ecommerce\Catalog\Model\Category\Command\Handler\Action\BuildUpdateCategoryExpected;
use Ecommerce\Catalog\Model\Category\Command\Validation\ValidateUpdateCategory;
use Ecommerce\Catalog\Model\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Category\Event\CategoryUpdated;
use Ecommerce\Catalog\Model\Category\Repository\CategoryRepository;
use Ecommerce\Catalog\Model\Category\Repository\ConcurrencyConflictException;
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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        try {
            /** @var CommandUpdateCategory $updateCategory */
            $updateCategory = $this->payload();

            $this->handle(ValidateUpdateCategory::class)($updateCategory);

            $query = $this->handle(QueryCategory::class, identifier: $updateCategory->categoryIdentifier);
            /** @var Category|null $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryIdentifier' => $updateCategory->categoryIdentifier]
                        )
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
                'categoryIdentifier' => $updateCategory->categoryIdentifier,
            ]);

            $event = $this->handle(
                CategoryUpdated::class,
                categoryIdentifier: $handler->getData()->getIdentifier(),
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
