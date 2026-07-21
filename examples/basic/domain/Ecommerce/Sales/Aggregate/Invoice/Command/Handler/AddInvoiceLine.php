<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Aggregate\Invoice\Command\Handler\Action\BuildAddInvoiceLineData;
use Ecommerce\Sales\Aggregate\Invoice\Command\Validation\ValidateAddInvoiceLine;
use Ecommerce\Sales\Aggregate\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Invoice\Event\InvoiceInvoiceLineAdded;
use Ecommerce\Sales\Aggregate\Invoice\Repository\InvoiceRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Command\AddInvoiceLine as CommandAddInvoiceLine;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceByIdentifier as QueryInvoice;

/**
 * Command endpoint: AddInvoiceLine
 *
 * Operation: add InvoiceLine (many)
 */
class AddInvoiceLine extends EcommerceContext
{
    /**
     * Adds the InvoiceLine entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddInvoiceLine $addInvoiceLine */
            $addInvoiceLine = $this->payload();

            $query = $this->handle(QueryInvoice::class, identifier: $addInvoiceLine->invoiceIdentifier);
            /** @var Invoice $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);

            $this->handle(ValidateAddInvoiceLine::class)($addInvoiceLine);
            $entityData = $this->handle(BuildAddInvoiceLineData::class)($addInvoiceLine);

            $handler->addInvoiceLine($entityData);

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $invoiceLine = $handler->getData()->getInvoiceLine();
            $this->result()->setData([
                'invoiceIdentifier' => $addInvoiceLine->invoiceIdentifier,
                'invoiceLineIdentifier' => $invoiceLine[array_key_last($invoiceLine)]->getIdentifier(),
            ]);

            $invoiceLineForEvent = $handler->getData()->getInvoiceLine();
            $addedEntity = !empty($invoiceLineForEvent) ? $invoiceLineForEvent[array_key_last($invoiceLineForEvent)] : null;

            $event = $this->handle(
                InvoiceInvoiceLineAdded::class,
                invoiceIdentifier: $handler->getData()->getIdentifier(),
                invoiceLineIdentifier: $addedEntity?->getIdentifier(),
                position: $addedEntity?->getPosition(),
                description: $addedEntity?->getDescription(),
                quantity: $addedEntity?->getQuantity(),
                unit: $addedEntity?->getUnit(),
                unitPrice: $addedEntity?->getUnitPrice(),
                discountPercent: $addedEntity?->getDiscountPercent(),
                lineTotal: $addedEntity?->getLineTotal(),
                taxIncluded: $addedEntity?->getTaxIncluded(),
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
