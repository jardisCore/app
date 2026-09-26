<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\Action\HydrateCreateInvoiceEntities;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidateCreateInvoice;
use Ecommerce\Sales\Model\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceCreated;
use Ecommerce\Sales\Model\Invoice\Repository\InvoiceRepository;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Closure\Guard\GuardInvoice;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
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
     * @throws Exception
     * @throws Throwable
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
                $this->result()->setData([
                    'rule' => $guardResult->rule,
                    'messageKey' => $guardResult->messageKey,
                    'context' => $guardResult->context,
                ]);

                return $this->handle(DomainResponseTransformer::class)->transform(
                    $this->result(),
                    ResponseStatus::RuleViolation
                );
            }

            $this->handle(HydrateCreateInvoiceEntities::class)($handler, $invoice);

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['invoiceIdentifier' => $handler->getData()->getIdentifier()]);

            $event = $this->handle(InvoiceCreated::class, invoiceIdentifier: $handler->getData()->getIdentifier(), occurredAt: new DateTimeImmutable());
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::Created);
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
