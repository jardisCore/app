<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Category\Aggregate\Category;
use Ecommerce\Catalog\Aggregate\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Category\Event\CategoryCategoryTranslationRemoved;
use Ecommerce\Catalog\Aggregate\Category\Repository\CategoryRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Command\RemoveCategoryTranslation as CommandRemoveCategoryTranslation;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryByIdentifier as QueryCategory;

/**
 * Command endpoint: RemoveCategoryTranslation
 *
 * Operation: remove CategoryTranslation (many)
 */
class RemoveCategoryTranslation extends EcommerceContext
{
    /**
     * Removes a CategoryTranslation from the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveCategoryTranslation $removeCategoryTranslation */
            $removeCategoryTranslation = $this->payload();

            $query = $this->handle(QueryCategory::class, identifier: $removeCategoryTranslation->categoryIdentifier);
            /** @var Category $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);

            $items = $handler->getData()->getCategoryTranslation();
            $found = false;
            foreach ($items as $item) {
                if ($item->getId() === $removeCategoryTranslation->categoryTranslationId) {
                    $handler->removeCategoryTranslation($item->getId());
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("CategoryTranslation not found: " . $removeCategoryTranslation->categoryTranslationId);
            }

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['categoryIdentifier' => $removeCategoryTranslation->categoryIdentifier]);

            $event = $this->handle(
                CategoryCategoryTranslationRemoved::class,
                categoryIdentifier: $handler->getData()->getIdentifier(),
                categoryTranslationId: $removeCategoryTranslation->categoryTranslationId,
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
