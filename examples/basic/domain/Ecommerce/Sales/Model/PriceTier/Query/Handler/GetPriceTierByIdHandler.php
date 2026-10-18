<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Query\Response\PriceTierResponse;
use Ecommerce\Sales\Model\PriceTier\Repository\PriceTierRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Model\PriceTier\Query\PriceTierById as QueryPriceTierById;

/**
 * Query endpoint: GetPriceTierById
 */
class GetPriceTierByIdHandler extends EcommerceContext
{
    /**
     * Executes the Query endpoint operation.
     *
     * @return DomainResponseInterface
     * @throws Throwable
     * @throws Exception
     * @throws ReflectionException
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var QueryPriceTierById $priceTierById */
        $priceTierById = $this->payload();

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(PriceTierRepository::class)->getPriceTierById($priceTierById, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'priceTier' => [
                'identifier' => 'identifier',
                'tierCode' => 'tier_code',
                'minQuantity' => 'min_quantity',
                'unitPrice' => 'unit_price',
                'createdAt' => 'created_at',
            ],
        ];
        $mapEntity = static fn(string $entity): array => $readMaps[$entity] ?? [];

        $wireTypes = [
            'createdAt' => 'date-time',
        ];

        $projectWire = $this->handle(ProjectWire::class);
        $fieldMapper = $this->handle(FieldMapper::class);
        $hydration = $this->handle(Hydration::class);

        /** @var array<int, PriceTierResponse> $projected */
        $projected = array_map(
            function (array $row) use (
                $mapEntity,
                $fieldMapper,
                $hydration,
                $projectWire,
                $wireTypes
            ): PriceTierResponse {
                $mapped = $fieldMapper->fromAggregate($row, $mapEntity, 'priceTier');
                $mapped = $projectWire($mapped, $wireTypes);
                $priceTierResponse = new PriceTierResponse();
                $hydration->hydrateAggregate($priceTierResponse, $mapped);
                return $priceTierResponse;
            },
            $result,
        );

        $found = $projected[0] ?? null;
        $this->result()->setData($found === null ? [] : ['@type' => 'priceTier'] + get_object_vars($found));

        return ($this->transformer())($this->result());
    }
}
