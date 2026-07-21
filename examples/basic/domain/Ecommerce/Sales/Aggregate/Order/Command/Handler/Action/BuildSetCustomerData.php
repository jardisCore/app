<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\SetCustomer as CommandSetCustomer;

/**
 * Action: BuildSetCustomerData
 */
class BuildSetCustomerData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandSetCustomer $setCustomer Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandSetCustomer $setCustomer): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($setCustomer);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["orderNumber","address"], true),
                ARRAY_FILTER_USE_KEY
            ),
            'address' => $setCustomer->address !== null
                ? $this->handle(FieldMapper::class)->toColumns(get_object_vars($setCustomer->address), $fieldMap->addressesColumns()) : null,
        ], $fieldMap->customersColumns());
    }
}
