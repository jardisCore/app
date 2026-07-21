<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Category\Aggregate\Category;
use Ecommerce\Catalog\Aggregate\Category\Command\Handler\Action\BuildAddCategoryTranslationData;
use Ecommerce\Catalog\Aggregate\Category\Command\Validation\ValidateAddCategoryTranslation;
use Ecommerce\Catalog\Aggregate\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Category\Event\CategoryCategoryTranslationAdded;
use Ecommerce\Catalog\Aggregate\Category\Repository\CategoryRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Command\AddCategoryTranslation as CommandAddCategoryTranslation;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryByIdentifier as QueryCategory;

/**
 * Command endpoint: AddCategoryTranslation
 *
 * Operation: add CategoryTranslation (many)
 */
class AddCategoryTranslation extends EcommerceContext
{
    /**
     * Adds the CategoryTranslation entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddCategoryTranslation $addCategoryTranslation */
            $addCategoryTranslation = $this->payload();

            $query = $this->handle(QueryCategory::class, identifier: $addCategoryTranslation->categoryIdentifier);
            /** @var Category $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);

            $this->handle(ValidateAddCategoryTranslation::class)($addCategoryTranslation);
            $entityData = $this->handle(BuildAddCategoryTranslationData::class)($addCategoryTranslation);

            $handler->addCategoryTranslation($entityData);

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $categoryTranslation = $handler->getData()->getCategoryTranslation();
            $this->result()->setData([
                'categoryIdentifier' => $addCategoryTranslation->categoryIdentifier,
                'categoryTranslationId' => $categoryTranslation[array_key_last($categoryTranslation)]->getId(),
            ]);

            $categoryTranslationForEvent = $handler->getData()->getCategoryTranslation();
            $addedEntity = !empty($categoryTranslationForEvent) ? $categoryTranslationForEvent[array_key_last($categoryTranslationForEvent)] : null;

            $event = $this->handle(
                CategoryCategoryTranslationAdded::class,
                categoryIdentifier: $handler->getData()->getIdentifier(),
                categoryTranslationId: $addedEntity?->getId(),
                locale: $addedEntity?->getLocale(),
                title: $addedEntity?->getTitle(),
                description: $addedEntity?->getDescription(),
                metaTitle: $addedEntity?->getMetaTitle(),
                metaDescription: $addedEntity?->getMetaDescription(),
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
