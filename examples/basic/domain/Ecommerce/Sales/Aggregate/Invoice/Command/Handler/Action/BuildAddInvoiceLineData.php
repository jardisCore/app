<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Command\AddInvoiceLine as CommandAddInvoiceLine;

/**
 * Action: BuildAddInvoiceLineData
 */
class BuildAddInvoiceLineData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandAddInvoiceLine $addInvoiceLine Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandAddInvoiceLine $addInvoiceLine): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($addInvoiceLine);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["invoiceIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->invoiceLinesColumns());
    }
}
