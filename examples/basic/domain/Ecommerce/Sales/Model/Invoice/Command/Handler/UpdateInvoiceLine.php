<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\BuildUpdateInvoiceLineData;
use Ecommerce\Sales\Model\Invoice\Command\Validation\UpdateInvoiceLineValidatorRegistry;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceInvoiceLineUpdated;
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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandUpdateInvoiceLine $updateInvoiceLine */
        $updateInvoiceLine = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateInvoiceLineValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateInvoiceLine);
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
            $query = $this->handle(
                QueryInvoice::class,
                tenant: $updateInvoiceLine->tenant,
                identifier: $updateInvoiceLine->invoiceIdentifier
            );
            /** @var Invoice|null $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceIdentifier' => $updateInvoiceLine->invoiceIdentifier]
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
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['invoiceLineIdentifier' => $updateInvoiceLine->invoiceLineIdentifier]
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Invoice');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateInvoiceLine');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
