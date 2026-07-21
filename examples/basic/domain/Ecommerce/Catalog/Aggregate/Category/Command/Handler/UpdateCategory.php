<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Category\Aggregate\Category;
use Ecommerce\Catalog\Aggregate\Category\Command\Handler\Action\BuildUpdateCategoryData;
use Ecommerce\Catalog\Aggregate\Category\Command\Validation\ValidateUpdateCategory;
use Ecommerce\Catalog\Aggregate\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Category\Event\CategoryUpdated;
use Ecommerce\Catalog\Aggregate\Category\Repository\CategoryRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Command\UpdateCategory as CommandUpdateCategory;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryByIdentifier as QueryCategory;

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
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandUpdateCategory $updateCategory */
            $updateCategory = $this->payload();

            $query = $this->handle(QueryCategory::class, identifier: $updateCategory->categoryIdentifier);
            /** @var Category $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);

            $this->handle(ValidateUpdateCategory::class)($updateCategory);
            $entityData = $this->handle(BuildUpdateCategoryData::class)($updateCategory);

            $handler->setCategory($entityData);

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['categoryIdentifier' => $updateCategory->categoryIdentifier]);

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
