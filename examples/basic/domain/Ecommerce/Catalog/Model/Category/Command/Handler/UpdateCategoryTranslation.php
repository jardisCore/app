<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category;
use Ecommerce\Catalog\Model\Category\Command\Handler\Action\BuildUpdateCategoryTranslationData;
use Ecommerce\Catalog\Model\Category\Command\Validation\ValidateUpdateCategoryTranslation;
use Ecommerce\Catalog\Model\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Category\Event\CategoryCategoryTranslationUpdated;
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
use Ecommerce\Catalog\Model\Category\Command\UpdateCategoryTranslation as CommandUpdateCategoryTranslation;
use Ecommerce\Catalog\Model\Category\Query\CategoryByIdentifier as QueryCategory;

/**
 * Command endpoint: UpdateCategoryTranslation
 *
 * Operation: update CategoryTranslation (many)
 */
class UpdateCategoryTranslation extends EcommerceContext
{
    /**
     * Updates a CategoryTranslation in the collection.
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
            /** @var CommandUpdateCategoryTranslation $updateCategoryTranslation */
            $updateCategoryTranslation = $this->payload();

            $this->handle(ValidateUpdateCategoryTranslation::class)($updateCategoryTranslation);

            $query = $this->handle(QueryCategory::class, identifier: $updateCategoryTranslation->categoryIdentifier);
            /** @var Category|null $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryIdentifier' => $updateCategoryTranslation->categoryIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildUpdateCategoryTranslationData::class)($updateCategoryTranslation);

            $items = $handler->getData()->getCategoryTranslation();
            $found = false;
            foreach ($items as $item) {
                if ($item->getId() === $updateCategoryTranslation->categoryTranslationId) {
                    $handler->updateCategoryTranslation($item->getId(), $entityData);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryTranslationId' => $updateCategoryTranslation->categoryTranslationId]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'category',
                'categoryIdentifier' => $updateCategoryTranslation->categoryIdentifier,
                'categoryTranslationId' => $updateCategoryTranslation->categoryTranslationId,
            ]);

            $event = $this->handle(
                CategoryCategoryTranslationUpdated::class,
                categoryIdentifier: $handler->getData()->getIdentifier(),
                categoryTranslationId: $updateCategoryTranslation->categoryTranslationId,
                locale: $updateCategoryTranslation->locale,
                title: $updateCategoryTranslation->title,
                description: $updateCategoryTranslation->description,
                metaTitle: $updateCategoryTranslation->metaTitle,
                metaDescription: $updateCategoryTranslation->metaDescription,
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
