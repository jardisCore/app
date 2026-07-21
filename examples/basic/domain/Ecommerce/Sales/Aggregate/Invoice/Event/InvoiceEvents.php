<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Event;

/**
 * Invoice Event Registry.
 *
 * Provides access to all Invoice domain events.
 * Use constants for event subscriptions and getAll() for bulk registration.
 */
class InvoiceEvents
{
    public const INVOICE_CREATED = InvoiceCreated::class;
    public const INVOICE_REMOVED = InvoiceRemoved::class;
    public const INVOICE_UPDATED = InvoiceUpdated::class;
    public const INVOICE_INVOICE_LINE_ADDED = InvoiceInvoiceLineAdded::class;
    public const INVOICE_INVOICE_LINE_REMOVED = InvoiceInvoiceLineRemoved::class;

    public static function getAll(): array
    {
        return [
            'InvoiceCreated' => self::INVOICE_CREATED,
            'InvoiceRemoved' => self::INVOICE_REMOVED,
            'InvoiceUpdated' => self::INVOICE_UPDATED,
            'InvoiceInvoiceLineAdded' => self::INVOICE_INVOICE_LINE_ADDED,
            'InvoiceInvoiceLineRemoved' => self::INVOICE_INVOICE_LINE_REMOVED,
        ];
    }
}
