<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\BuildUpdateInvoiceData;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\BuildUpdateInvoiceExpected;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidateUpdateInvoice;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceUpdated;
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
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Command\UpdateInvoice as CommandUpdateInvoice;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceByIdentifier as QueryInvoice;

/**
 * Command endpoint: UpdateInvoice
 *
 * Operation: update Invoice (root)
 */
class UpdateInvoice extends EcommerceContext
{
    /**
     * Updates the root Invoice entity.
     *
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        try {
            /** @var CommandUpdateInvoice $updateInvoice */
            $updateInvoice = $this->payload();

            $this->handle(ValidateUpdateInvoice::class)($updateInvoice);

            $query = $this->handle(QueryInvoice::class, identifier: $updateInvoice->invoiceIdentifier);
            /** @var Invoice|null $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceIdentifier' => $updateInvoice->invoiceIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildUpdateInvoiceData::class)($updateInvoice);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildUpdateInvoiceExpected::class)($expected));
            }

            $handler->setInvoice($entityData);

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'invoice',
                'invoiceIdentifier' => $updateInvoice->invoiceIdentifier,
            ]);

            $event = $this->handle(
                InvoiceUpdated::class,
                invoiceIdentifier: $handler->getData()->getIdentifier(),
                invoiceNumber: $handler->getData()->getInvoiceNumber(),
                orderNumber: $handler->getData()->getOrderNumber(),
                customerIdentifier: $handler->getData()->getCustomerIdentifier(),
                status: $handler->getData()->getStatus()?->value ?? throw new RuntimeException('status is required'),
                paymentMethod: $handler->getData()->getPaymentMethod()?->value ?? null,
                totalNet: $handler->getData()->getTotalNet(),
                taxRate: $handler->getData()->getTaxRate(),
                totalGross: $handler->getData()->getTotalGross(),
                currency: $handler->getData()->getCurrency(),
                note: $handler->getData()->getNote(),
                issuedAt: $handler->getData()->getIssuedAt() !== null
                    ? DateTimeImmutable::createFromInterface($handler->getData()->getIssuedAt())
                    : null,
                dueAt: $handler->getData()->getDueAt() !== null
                    ? DateTimeImmutable::createFromInterface($handler->getData()->getDueAt())
                    : null,
                paidAt: $handler->getData()->getPaidAt() !== null
                    ? DateTimeImmutable::createFromInterface($handler->getData()->getPaidAt())
                    : null,
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
