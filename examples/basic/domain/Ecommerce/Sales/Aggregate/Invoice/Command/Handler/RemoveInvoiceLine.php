<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\Aggregate\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Invoice\Event\InvoiceInvoiceLineRemoved;
use Ecommerce\Sales\Aggregate\Invoice\Repository\InvoiceRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Command\RemoveInvoiceLine as CommandRemoveInvoiceLine;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceByIdentifier as QueryInvoice;

/**
 * Command endpoint: RemoveInvoiceLine
 *
 * Operation: remove InvoiceLine (many)
 */
class RemoveInvoiceLine extends EcommerceContext
{
    /**
     * Removes an InvoiceLine from the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveInvoiceLine $removeInvoiceLine */
            $removeInvoiceLine = $this->payload();

            $query = $this->handle(QueryInvoice::class, identifier: $removeInvoiceLine->invoiceIdentifier);
            /** @var Invoice $handler */
            $handler = $this->handle(InvoiceRepository::class)->getInvoiceByIdentifier($query);

            $items = $handler->getData()->getInvoiceLine();
            $found = false;
            foreach ($items as $item) {
                if ($item->getIdentifier() === $removeInvoiceLine->invoiceLineIdentifier) {
                    $handler->removeInvoiceLine($item->getId());
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("InvoiceLine not found: " . $removeInvoiceLine->invoiceLineIdentifier);
            }

            $persistResult = $this->handle(InvoiceRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['invoiceIdentifier' => $removeInvoiceLine->invoiceIdentifier]);

            $event = $this->handle(
                InvoiceInvoiceLineRemoved::class,
                invoiceIdentifier: $handler->getData()->getIdentifier(),
                invoiceLineIdentifier: $removeInvoiceLine->invoiceLineIdentifier,
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
