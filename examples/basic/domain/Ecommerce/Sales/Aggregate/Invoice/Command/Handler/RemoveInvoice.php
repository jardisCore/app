<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Aggregate\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Invoice\Event\InvoiceRemoved;
use Ecommerce\Sales\Aggregate\Invoice\Repository\InvoiceRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Command\RemoveInvoice as CommandRemoveInvoice;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceByIdentifier as QueryInvoice;

/**
 * Command endpoint: RemoveInvoice
 */
class RemoveInvoice extends EcommerceContext
{
    /**
     * Removes the Invoice aggregate.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveInvoice $removeInvoice */
            $removeInvoice = $this->payload();

            $query = $this->handle(QueryInvoice::class, identifier: $removeInvoice->invoiceIdentifier);
            /** @var Invoice $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);

            $handler->remove();

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['invoiceIdentifier' => $removeInvoice->invoiceIdentifier]);

            $event = $this->handle(InvoiceRemoved::class, invoiceIdentifier: $handler->getData()->getIdentifier(), occurredAt: new DateTimeImmutable());
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
