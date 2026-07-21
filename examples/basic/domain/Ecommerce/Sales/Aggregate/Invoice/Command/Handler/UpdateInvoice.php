<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Aggregate\Invoice\Command\Handler\Action\BuildUpdateInvoiceData;
use Ecommerce\Sales\Aggregate\Invoice\Command\Validation\ValidateUpdateInvoice;
use Ecommerce\Sales\Aggregate\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Invoice\Event\InvoiceUpdated;
use Ecommerce\Sales\Aggregate\Invoice\Repository\InvoiceRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Command\UpdateInvoice as CommandUpdateInvoice;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceByIdentifier as QueryInvoice;

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
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandUpdateInvoice $updateInvoice */
            $updateInvoice = $this->payload();

            $query = $this->handle(QueryInvoice::class, identifier: $updateInvoice->invoiceIdentifier);
            /** @var Invoice $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);

            $this->handle(ValidateUpdateInvoice::class)($updateInvoice);
            $entityData = $this->handle(BuildUpdateInvoiceData::class)($updateInvoice);

            $handler->setInvoice($entityData);

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['invoiceIdentifier' => $updateInvoice->invoiceIdentifier]);

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
                issuedAt: $handler->getData()->getIssuedAt() !== null ? DateTimeImmutable::createFromInterface($handler->getData()->getIssuedAt()) : null,
                dueAt: $handler->getData()->getDueAt() !== null ? DateTimeImmutable::createFromInterface($handler->getData()->getDueAt()) : null,
                paidAt: $handler->getData()->getPaidAt() !== null ? DateTimeImmutable::createFromInterface($handler->getData()->getPaidAt()) : null,
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
