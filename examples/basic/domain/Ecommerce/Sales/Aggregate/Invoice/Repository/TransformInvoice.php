<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Repository;

use Ecommerce\EcommerceContext;

/**
 * Transforms flat query results into nested Invoice structure.
 *
 * Input: Arrays grouped by entity name (from query results)
 * Output: Nested array structure matching aggregate definition
 */
class TransformInvoice extends EcommerceContext
{
    /**
     * Transform flat query results into nested aggregate structure.
     *
     * @param array<string, array<int, mixed>> $container Query results grouped by entity
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(array $container = []): array
    {
        $result = [];
        $dto = [];
        $processedIds = [];

        foreach ($container['invoice'] as $index => $invoice) {
            if (is_numeric($index) && !isset($processedIds[$invoice['id']])) {
                $processedIds[$invoice['id']] = true;
                $parent = $dto['invoice'] = $invoice;

                $this->transformInvoiceInvoiceLine($parent, $dto, $container);

                $result[] = $parent;
            }
        }

        return $result;
    }

    /**
     * Transform invoiceLine entities (ERM: many).
     * Relates: invoice_id = invoice.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformInvoiceInvoiceLine(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['invoiceLine'] as $index => $invoiceLine) {
            if (is_numeric($index) && $invoiceLine['invoice_id'] == $dto['invoice']['id']) {
                $parent['invoiceLine'][] = $invoiceLine;
            }
        }
    }
}
