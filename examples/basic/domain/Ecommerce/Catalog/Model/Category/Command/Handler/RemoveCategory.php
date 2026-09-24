<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category;
use Ecommerce\Catalog\Model\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Category\Event\CategoryRemoved;
use Ecommerce\Catalog\Model\Category\Repository\CategoryRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Catalog\Model\Category\Command\RemoveCategory as CommandRemoveCategory;
use Ecommerce\Catalog\Model\Category\Query\CategoryByIdentifier as QueryCategory;

/**
 * Command endpoint: RemoveCategory
 */
class RemoveCategory extends EcommerceContext
{
    /**
     * Removes the Category aggregate.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveCategory $removeCategory */
            $removeCategory = $this->payload();

            $query = $this->handle(QueryCategory::class, identifier: $removeCategory->categoryIdentifier);
            /** @var Category $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);

            $handler->remove();

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['categoryIdentifier' => $removeCategory->categoryIdentifier]);

            $event = $this->handle(CategoryRemoved::class, categoryIdentifier: $handler->getData()->getIdentifier(), occurredAt: new DateTimeImmutable());
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());
            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::ValidationError);
        } catch (\Throwable $e) {
            $this->result()->addError($e->getMessage());
            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::InternalError);
        }
    }
}
