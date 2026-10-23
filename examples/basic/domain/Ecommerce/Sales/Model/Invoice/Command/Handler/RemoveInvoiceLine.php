<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceInvoiceLineRemoved;
use Ecommerce\Sales\Model\Invoice\Repository\InvoiceRepository;
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
use Ecommerce\Sales\Model\Invoice\Command\RemoveInvoiceLine as CommandRemoveInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceByIdentifier as QueryInvoice;

/**
 * Command endpoint: RemoveInvoiceLine
 *
 * Operation: remove InvoiceLine (many)
 */
class RemoveInvoiceLine extends EcommerceContext
{
    /**
     * Removes an InvoiceLine from the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandRemoveInvoiceLine $removeInvoiceLine */
        $removeInvoiceLine = $this->payload();
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(QueryInvoice::class, identifier: $removeInvoiceLine->invoiceIdentifier);
            /** @var Invoice|null $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceIdentifier' => $removeInvoiceLine->invoiceIdentifier]
                        ),
                        null,
                        ''
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $items = $handler->getData()->getInvoiceLine();
            $found = false;
            foreach ($items as $item) {
                if ($item->getIdentifier() === $removeInvoiceLine->invoiceLineIdentifier) {
                    $handler->removeInvoiceLine($item->getId());
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceLineIdentifier' => $removeInvoiceLine->invoiceLineIdentifier]
                        ),
                        null,
                        ''
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'invoice',
                'id' => $handler->getData()->getId(),
                'invoiceIdentifier' => $removeInvoiceLine->invoiceIdentifier,
            ]);

            $event = $this->handle(
                InvoiceInvoiceLineRemoved::class,
                invoiceIdentifier: $handler->getData()->getIdentifier(),
                invoiceLineIdentifier: $removeInvoiceLine->invoiceLineIdentifier,
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Invoice');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'RemoveInvoiceLine');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
