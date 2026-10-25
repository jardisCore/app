<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Command\UpdateInvoiceLine as CommandUpdateInvoiceLine;

/**
 * Action: BuildUpdateInvoiceLineData
 */
class BuildUpdateInvoiceLineData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateInvoiceLine $updateInvoiceLine Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateInvoiceLine $updateInvoiceLine): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateInvoiceLine);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["tenant","invoiceIdentifier","invoiceLineIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->invoiceLinesColumns());
    }
}
