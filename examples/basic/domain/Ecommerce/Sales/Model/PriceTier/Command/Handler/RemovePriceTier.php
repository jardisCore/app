<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Aggregate\PriceTier;
use Ecommerce\Sales\Model\PriceTier\Event\PriceTierRemoved;
use Ecommerce\Sales\Model\PriceTier\Repository\PriceTierRepository;
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
use Ecommerce\Sales\Model\PriceTier\Command\RemovePriceTier as CommandRemovePriceTier;
use Ecommerce\Sales\Model\PriceTier\Query\PriceTierByIdentifier as QueryPriceTier;

/**
 * Command endpoint: RemovePriceTier
 */
class RemovePriceTier extends EcommerceContext
{
    /**
     * Removes the PriceTier aggregate.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandRemovePriceTier $removePriceTier */
        $removePriceTier = $this->payload();
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(
                QueryPriceTier::class,
                tenant: $removePriceTier->tenant,
                identifier: $removePriceTier->priceTierIdentifier
            );
            /** @var PriceTier|null $handler */
            $handler = $this->handle(PriceTierRepository::class)->getPriceTierByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['priceTierIdentifier' => $removePriceTier->priceTierIdentifier]
                        ),
                        null,
                        ''
                    )
                );
                return ($this->transformer())($this->result(), ResponseStatus::NotFound);
            }

            $handler->remove();

            $persistResult = $this->handle(PriceTierRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'priceTier',
                'id' => $handler->getData()->getId(),
                'priceTierIdentifier' => $removePriceTier->priceTierIdentifier,
            ]);

            $event = $this->handle(
                PriceTierRemoved::class,
                priceTierIdentifier: $handler->getData()->getIdentifier(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'PriceTier');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'RemovePriceTier');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
