<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Aggregate\Category\Aggregate\Category;
use Ecommerce\Catalog\Aggregate\Category\Command\Handler\Action\HydrateCreateCategoryEntities;
use Ecommerce\Catalog\Aggregate\Category\Command\Validation\ValidateCreateCategory;
use Ecommerce\Catalog\Aggregate\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Aggregate\Category\Event\CategoryCreated;
use Ecommerce\Catalog\Aggregate\Category\Repository\CategoryRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Command\Category as CommandCategory;

/**
 * Command endpoint: CreateCategory
 *
 * Creates a new Category aggregate
 */
class CreateCategory extends EcommerceContext
{
    /**
     * Creates a new Category.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandCategory $category */
            $category = $this->payload();

            /** @var Category $handler */
            $handler = $this->handle(CategoryRepository::class)->createNew();
            $this->handle(ValidateCreateCategory::class)($category);
            $this->handle(HydrateCreateCategoryEntities::class)($handler, $category);

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['categoryIdentifier' => $handler->getData()->getIdentifier()]);

            $event = $this->handle(CategoryCreated::class, categoryIdentifier: $handler->getData()->getIdentifier(), occurredAt: new DateTimeImmutable());
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::Created);
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
