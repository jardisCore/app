<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\BuildUpdateInvoiceData;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\BuildUpdateInvoiceExpected;
use Ecommerce\Sales\Model\Invoice\Command\Validation\UpdateInvoiceValidatorRegistry;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceUpdated;
use Ecommerce\Sales\Model\Invoice\Repository\InvoiceRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
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
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        /** @var CommandUpdateInvoice $updateInvoice */
        $updateInvoice = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateInvoiceValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateInvoice);
        if ($findings->fields !== []) {
            foreach ($findings->errors as $error) {
                $this->result()->addError($error);
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))(
                    ResponseStatus::ValidationError,
                    ($this->handle(ValidationPayload::class))('validation', $findings->fields),
                    null,
                    ''
                )
            );

            return ($this->transformer())($this->result(), ResponseStatus::ValidationError);
        }
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(QueryInvoice::class, identifier: $updateInvoice->invoiceIdentifier);
            /** @var Invoice|null $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceIdentifier' => $updateInvoice->invoiceIdentifier]
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

            $entityData = $this->handle(BuildUpdateInvoiceData::class)($updateInvoice);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildUpdateInvoiceExpected::class)($expected));
            }

            $handler->setInvoice($entityData);

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'invoice',
                'id' => $handler->getData()->getId(),
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Invoice');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateInvoice');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
