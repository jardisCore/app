<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category;
use Ecommerce\Catalog\Model\Category\Event\CategoryRemoved;
use Ecommerce\Catalog\Model\Category\Repository\CategoryRepository;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
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
        /** @var CommandRemoveCategory $removeCategory */
        $removeCategory = $this->payload();
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(
                QueryCategory::class,
                tenant: $removeCategory->tenant,
                identifier: $removeCategory->categoryIdentifier
            );
            /** @var Category|null $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryIdentifier' => $removeCategory->categoryIdentifier]
                        ),
                        null,
                        ''
                    )
                );
                return ($this->transformer())($this->result(), ResponseStatus::NotFound);
            }

            $handler->remove();

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'category',
                'id' => $handler->getData()->getId(),
                'categoryIdentifier' => $removeCategory->categoryIdentifier,
            ]);

            $event = $this->handle(
                CategoryRemoved::class,
                categoryIdentifier: $handler->getData()->getIdentifier(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Category');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'RemoveCategory');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
