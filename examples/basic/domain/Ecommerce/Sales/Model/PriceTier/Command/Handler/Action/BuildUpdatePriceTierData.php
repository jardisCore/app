<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Model\PriceTier\Command\UpdatePriceTier as CommandUpdatePriceTier;

/**
 * Action: BuildUpdatePriceTierData
 */
class BuildUpdatePriceTierData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdatePriceTier $updatePriceTier Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdatePriceTier $updatePriceTier): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updatePriceTier);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["priceTierIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->priceTiersColumns());
    }
}
