<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use Ecommerce\Sales\Model\PriceTier\Aggregate\PriceTier;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Model\PriceTier\Command\PriceTier as CommandPriceTier;

/**
 * Action: HydrateCreatePriceTierEntities
 */
class HydrateCreatePriceTierEntities extends EcommerceContext
{
    /**
     * Hydrates all entities from the command DTO.
     *
     * @param PriceTier $handler Aggregate handler
     * @param CommandPriceTier $priceTier Command data
     * @throws Throwable
     */
    public function __invoke(PriceTier $handler, CommandPriceTier $priceTier): void
    {
        $this->hydratePriceTier($handler, $priceTier);
    }

    /**
     * Hydrates PriceTier entity data.
     * @throws Throwable
     */
    protected function hydratePriceTier(PriceTier $handler, CommandPriceTier $priceTier): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($priceTier);

        $columns = $this->handle(FieldMapper::class)->toColumns(array_filter(
            $rawData,
            fn($key) => !in_array($key, ["tenant"], true),
            ARRAY_FILTER_USE_KEY
        ), $fieldMap->priceTiersColumns());
        $columns['tenant_key'] = $priceTier->tenant;
        $handler->setPriceTier($columns);
    }
}
