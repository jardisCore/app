<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\UpdateOrderItem as CommandUpdateOrderItem;

/**
 * Action: BuildUpdateOrderItemData
 */
class BuildUpdateOrderItemData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateOrderItem $updateOrderItem Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateOrderItem $updateOrderItem): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateOrderItem);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["tenant","orderNumber","orderItemIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->orderItemsColumns());
    }
}
