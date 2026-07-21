<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\AddItemDiscount as CommandAddItemDiscount;

/**
 * Action: BuildAddItemDiscountData
 */
class BuildAddItemDiscountData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandAddItemDiscount $addItemDiscount Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandAddItemDiscount $addItemDiscount): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($addItemDiscount);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["orderNumber","orderItemIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->itemDiscountsColumns());
    }
}
