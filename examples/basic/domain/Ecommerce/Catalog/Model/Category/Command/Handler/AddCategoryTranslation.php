<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category;
use Ecommerce\Catalog\Model\Category\Command\Handler\Action\BuildAddCategoryTranslationData;
use Ecommerce\Catalog\Model\Category\Command\Validation\ValidateAddCategoryTranslation;
use Ecommerce\Catalog\Model\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Category\Event\CategoryCategoryTranslationAdded;
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
use Ecommerce\Catalog\Model\Category\Command\AddCategoryTranslation as CommandAddCategoryTranslation;
use Ecommerce\Catalog\Model\Category\Query\CategoryByIdentifier as QueryCategory;

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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddCategoryTranslation $addCategoryTranslation */
            $addCategoryTranslation = $this->payload();

            $this->handle(ValidateAddCategoryTranslation::class)($addCategoryTranslation);

            $query = $this->handle(QueryCategory::class, identifier: $addCategoryTranslation->categoryIdentifier);
            /** @var Category|null $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryIdentifier' => $addCategoryTranslation->categoryIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildAddCategoryTranslationData::class)($addCategoryTranslation);

            $handler->addCategoryTranslation($entityData);

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $categoryTranslation = $handler->getData()->getCategoryTranslation();
            $this->result()->setData([
                '@type' => 'category',
                'categoryIdentifier' => $addCategoryTranslation->categoryIdentifier,
                'categoryTranslationId' => $categoryTranslation[array_key_last($categoryTranslation)]->getId(),
            ]);

            $categoryTranslationForEvent = $handler->getData()->getCategoryTranslation();
            $addedEntity = !empty($categoryTranslationForEvent)
                ? $categoryTranslationForEvent[array_key_last($categoryTranslationForEvent)]
                : null;

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
