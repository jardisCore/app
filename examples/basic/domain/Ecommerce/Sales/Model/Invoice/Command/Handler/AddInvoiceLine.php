<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\BuildAddInvoiceLineData;
use Ecommerce\Sales\Model\Invoice\Command\Validation\AddInvoiceLineValidatorRegistry;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceInvoiceLineAdded;
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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandAddInvoiceLine $addInvoiceLine */
        $addInvoiceLine = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(AddInvoiceLineValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($addInvoiceLine);
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
            $query = $this->handle(QueryInvoice::class, identifier: $addInvoiceLine->invoiceIdentifier);
            /** @var Invoice|null $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceIdentifier' => $addInvoiceLine->invoiceIdentifier]
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

            $entityData = $this->handle(BuildAddInvoiceLineData::class)($addInvoiceLine);

            $handler->addInvoiceLine($entityData);

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $invoiceLine = $handler->getData()->getInvoiceLine();
            $this->result()->setData([
                '@type' => 'invoice',
                'id' => $handler->getData()->getId(),
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Invoice');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'AddInvoiceLine');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
