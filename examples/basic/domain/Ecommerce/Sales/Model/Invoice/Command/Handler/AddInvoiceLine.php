<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\BuildAddInvoiceLineData;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidateAddInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceInvoiceLineAdded;
use Ecommerce\Sales\Model\Invoice\Repository\ConcurrencyConflictException;
use Ecommerce\Sales\Model\Invoice\Repository\InvoiceRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\NotFoundPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Command\AddInvoiceLine as CommandAddInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceByIdentifier as QueryInvoice;

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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddInvoiceLine $addInvoiceLine */
            $addInvoiceLine = $this->payload();

            $this->handle(ValidateAddInvoiceLine::class)($addInvoiceLine);

            $query = $this->handle(QueryInvoice::class, identifier: $addInvoiceLine->invoiceIdentifier);
            /** @var Invoice|null $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceIdentifier' => $addInvoiceLine->invoiceIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildAddInvoiceLineData::class)($addInvoiceLine);

            $handler->addInvoiceLine($entityData);

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $invoiceLine = $handler->getData()->getInvoiceLine();
            $this->result()->setData([
                '@type' => 'invoice',
                'invoiceIdentifier' => $addInvoiceLine->invoiceIdentifier,
                'invoiceLineIdentifier' => $invoiceLine[array_key_last($invoiceLine)]->getIdentifier(),
            ]);

            $invoiceLineForEvent = $handler->getData()->getInvoiceLine();
            $addedEntity = !empty($invoiceLineForEvent)
                ? $invoiceLineForEvent[array_key_last($invoiceLineForEvent)]
                : null;

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

            return ($this->transformer())($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());
            $this->result()->setData(
                $this->failureData(($this->handle(ValidationPayload::class))('validation', $e->fields))
            );

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::ValidationError
            );
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

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::Conflict
            );
        } catch (TransientConflictException $transient) {
            throw $transient;
        } catch (Throwable $e) {
            $this->result()->addError($e->getMessage());

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::InternalError
            );
        }
    }
}
