<?php

declare(strict_types=1);

namespace Ecommerce\Sales;

/**
 * Central naming map: DTO property names <-> DB column names, per table.
 *
 * Pure naming container (Schema + FieldMap.yaml overrides + default
 * ToPropertyName). One method per BC table:
 *   {table}Columns() — full DTO→column map for FieldMapper::toColumns (write path)
 *
 * The read path (G4 strip + root-id normalization) is aggregate-structural
 * and lives at the aggregate read edge (the query handler), not here.
 *
 * Loaded via $this->handle(FieldMap::class) for ClassVersion support.
 */
class FieldMap
{
    /** @return array<string, string> field -> column mapping */
    public function addressesColumns(): array
    {
        return [
            'id' => 'id',
            'street' => 'street',
            'city' => 'city',
            'postalCode' => 'postal_code',
            'country' => 'country',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function customersColumns(): array
    {
        return [
            'id' => 'id',
            'identifier' => 'identifier',
            'email' => 'email',
            'customerName' => 'name',
            'phone' => 'phone',
            'billingAddressId' => 'billing_address_id',
            'createdAt' => 'created_at',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function invoiceLinesColumns(): array
    {
        return [
            'id' => 'id',
            'identifier' => 'identifier',
            'invoiceId' => 'invoice_id',
            'position' => 'position',
            'description' => 'description',
            'quantity' => 'quantity',
            'unit' => 'unit',
            'unitPrice' => 'unit_price',
            'discountPercent' => 'discount_percent',
            'lineTotal' => 'line_total',
            'taxIncluded' => 'tax_included',
            'createdAt' => 'created_at',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function invoicesColumns(): array
    {
        return [
            'id' => 'id',
            'identifier' => 'identifier',
            'invoiceNumber' => 'invoice_number',
            'orderNumber' => 'order_number',
            'customerIdentifier' => 'customer_identifier',
            'status' => 'status',
            'paymentMethod' => 'payment_method',
            'totalNet' => 'total_net',
            'taxRate' => 'tax_rate',
            'totalGross' => 'total_gross',
            'currency' => 'currency',
            'note' => 'note',
            'issuedAt' => 'issued_at',
            'dueAt' => 'due_at',
            'paidAt' => 'paid_at',
            'createdAt' => 'created_at',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function itemDiscountsColumns(): array
    {
        return [
            'id' => 'id',
            'itemId' => 'item_id',
            'discountCode' => 'discount_code',
            'amount' => 'amount',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function orderItemsColumns(): array
    {
        return [
            'id' => 'id',
            'identifier' => 'identifier',
            'orderId' => 'order_id',
            'productIdentifier' => 'product_identifier',
            'quantity' => 'quantity',
            'unitPrice' => 'unit_price',
            'subtotal' => 'subtotal',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function ordersColumns(): array
    {
        return [
            'id' => 'id',
            'orderNumber' => 'order_number',
            'customerId' => 'customer_id',
            'totalAmount' => 'total_amount',
            'status' => 'status',
            'createdAt' => 'created_at',
        ];
    }
}
