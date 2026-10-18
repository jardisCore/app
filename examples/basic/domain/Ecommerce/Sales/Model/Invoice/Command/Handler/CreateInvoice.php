<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Closure\Guard\GuardInvoice;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\HydrateCreateInvoiceEntities;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidateCreateInvoice;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceCreated;
use Ecommerce\Sales\Model\Invoice\Repository\ConcurrencyConflictException;
use Ecommerce\Sales\Model\Invoice\Repository\InvoiceRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\RuleViolationPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Command\Invoice as CommandInvoice;

/**
 * Command endpoint: CreateInvoice
 *
 * Creates a new Invoice aggregate
 */
class CreateInvoice extends EcommerceContext
{
    /**
     * Creates a new Invoice.
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
            /** @var CommandInvoice $invoice */
            $invoice = $this->payload();

            /** @var Invoice $handler */
            $handler = $this->handle(InvoiceRepository::class)->createNew();
            $this->handle(ValidateCreateInvoice::class)($invoice);

            /** @var RuleResult $guardResult */
            $guardResult = ($this->handle(GuardInvoice::class))($invoice);
            if (!$guardResult->passed) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(RuleViolationPayload::class))(
                            $guardResult->rule,
                            $guardResult->messageKey,
                            $guardResult->context
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::RuleViolation
                );
            }

            $this->handle(HydrateCreateInvoiceEntities::class)($handler, $invoice);

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'invoice',
                'invoiceIdentifier' => $handler->getData()->getIdentifier(),
                'invoiceLineIdentifiers' => array_map(
                    fn($e) => $e->getIdentifier(),
                    $handler->getData()->getInvoiceLine()
                ),
            ]);

            $event = $this->handle(
                InvoiceCreated::class,
                invoiceIdentifier: $handler->getData()->getIdentifier(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result(), ResponseStatus::Created);
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
