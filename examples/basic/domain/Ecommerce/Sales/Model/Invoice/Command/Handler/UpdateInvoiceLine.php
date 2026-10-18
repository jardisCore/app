<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\BuildUpdateInvoiceLineData;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidateUpdateInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceInvoiceLineUpdated;
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
use Ecommerce\Sales\Model\Invoice\Command\UpdateInvoiceLine as CommandUpdateInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceByIdentifier as QueryInvoice;

/**
 * Command endpoint: UpdateInvoiceLine
 *
 * Operation: update InvoiceLine (many)
 */
class UpdateInvoiceLine extends EcommerceContext
{
    /**
     * Updates an InvoiceLine in the collection.
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
            /** @var CommandUpdateInvoiceLine $updateInvoiceLine */
            $updateInvoiceLine = $this->payload();

            $this->handle(ValidateUpdateInvoiceLine::class)($updateInvoiceLine);

            $query = $this->handle(QueryInvoice::class, identifier: $updateInvoiceLine->invoiceIdentifier);
            /** @var Invoice|null $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceIdentifier' => $updateInvoiceLine->invoiceIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildUpdateInvoiceLineData::class)($updateInvoiceLine);

            $items = $handler->getData()->getInvoiceLine();
            $found = false;
            foreach ($items as $item) {
                if ($item->getIdentifier() === $updateInvoiceLine->invoiceLineIdentifier) {
                    $handler->updateInvoiceLine($item->getId(), $entityData);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceLineIdentifier' => $updateInvoiceLine->invoiceLineIdentifier]
                        )
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
                'invoiceIdentifier' => $updateInvoiceLine->invoiceIdentifier,
                'invoiceLineIdentifier' => $updateInvoiceLine->invoiceLineIdentifier,
            ]);

            $event = $this->handle(
                InvoiceInvoiceLineUpdated::class,
                invoiceIdentifier: $handler->getData()->getIdentifier(),
                invoiceLineIdentifier: $updateInvoiceLine->invoiceLineIdentifier,
                position: $updateInvoiceLine->position,
                description: $updateInvoiceLine->description,
                quantity: $updateInvoiceLine->quantity,
                unit: $updateInvoiceLine->unit,
                unitPrice: $updateInvoiceLine->unitPrice,
                discountPercent: $updateInvoiceLine->discountPercent,
                lineTotal: $updateInvoiceLine->lineTotal,
                taxIncluded: $updateInvoiceLine->taxIncluded,
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
