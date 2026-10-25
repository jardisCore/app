<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Event\OrderRemoved;
use Ecommerce\Sales\Model\Order\Repository\OrderRepository;
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
use Ecommerce\Sales\Model\Order\Command\RemoveOrder as CommandRemoveOrder;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: RemoveOrder
 */
class RemoveOrder extends EcommerceContext
{
    /**
     * Removes the Order aggregate.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandRemoveOrder $removeOrder */
        $removeOrder = $this->payload();
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(
                QueryOrder::class,
                tenant: $removeOrder->tenant,
                orderNumber: $removeOrder->orderNumber
            );
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $removeOrder->orderNumber]
                        ),
                        null,
                        ''
                    )
                );
                return ($this->transformer())($this->result(), ResponseStatus::NotFound);
            }

            $handler->remove();

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'order',
                'id' => $handler->getData()->getId(),
                'orderNumber' => $removeOrder->orderNumber,
            ]);

            $event = $this->handle(
                OrderRemoved::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Order');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'RemoveOrder');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
