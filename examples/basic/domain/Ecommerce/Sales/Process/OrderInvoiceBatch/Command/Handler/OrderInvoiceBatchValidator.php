<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler;

use Ecommerce\Sales\Model\Invoice\Command\AddInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\UpdateInvoice;
use Ecommerce\Sales\Model\Invoice\Command\Validation\InvoiceLineValidator;
use Ecommerce\Sales\Model\Invoice\Command\Validation\InvoiceValidator;
use Ecommerce\Sales\Model\Order\Command\AddOrderItem;
use Ecommerce\Sales\Model\Order\Command\ItemDiscount;
use Ecommerce\Sales\Model\Order\Command\UpdateOrder;
use Ecommerce\Sales\Model\Order\Command\Validation\ItemDiscountValidator;
use Ecommerce\Sales\Model\Order\Command\Validation\OrderItemValidator;
use Ecommerce\Sales\Model\Order\Command\Validation\OrderValidator;
use JardisSupport\Validation\ValidatorRegistry;

/**
 * Input gate of the process OrderInvoiceBatch: the validator registry of its input class graph.
 *
 * Hermetic - rules come from the types and value lists of the input fields.
 */
final class OrderInvoiceBatchValidator
{
    public function __invoke(): ValidatorRegistry
    {
        $registry = new ValidatorRegistry();
        $registry->register(UpdateOrder::class, (new OrderValidator())());
        $registry->register(UpdateInvoice::class, (new InvoiceValidator())());
        $registry->register(AddOrderItem::class, (new OrderItemValidator())());
        $registry->register(ItemDiscount::class, (new ItemDiscountValidator())());
        $registry->register(AddInvoiceLine::class, (new InvoiceLineValidator())());

        return $registry;
    }
}
