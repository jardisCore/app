<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\SetAddress as CommandSetAddress;

/**
 * Action: BuildSetAddressData
 */
class BuildSetAddressData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandSetAddress $setAddress Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandSetAddress $setAddress): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($setAddress);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["orderNumber"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->addressesColumns());
    }
}
