<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category;
use Ecommerce\Catalog\Model\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Model\Category\Event\CategoryRemoved;
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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveCategory $removeCategory */
            $removeCategory = $this->payload();

            $query = $this->handle(QueryCategory::class, identifier: $removeCategory->categoryIdentifier);
            /** @var Category|null $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryIdentifier' => $removeCategory->categoryIdentifier]
                        )
                    )
                );
                return ($this->transformer())($this->result(), ResponseStatus::NotFound);
            }

            $handler->remove();

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'category',
                'categoryIdentifier' => $removeCategory->categoryIdentifier,
            ]);

            $event = $this->handle(
                CategoryRemoved::class,
                categoryIdentifier: $handler->getData()->getIdentifier(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());
            $this->result()->setData(
                $this->failureData(($this->handle(ValidationPayload::class))('validation', $e->fields))
            );
            return ($this->transformer())($this->result(), ResponseStatus::ValidationError);
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
            return ($this->transformer())($this->result(), ResponseStatus::Conflict);
        } catch (TransientConflictException $transient) {
            throw $transient;
        } catch (Throwable $e) {
            $this->result()->addError($e->getMessage());
            return ($this->transformer())($this->result(), ResponseStatus::InternalError);
        }
    }
}
