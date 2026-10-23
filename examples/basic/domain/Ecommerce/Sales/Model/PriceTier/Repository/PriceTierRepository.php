<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Aggregate\Entity\PriceTier as PriceTierAggregate;
use Ecommerce\Sales\Model\PriceTier\Aggregate\PriceTier as PriceTierHandler;
use Ecommerce\Sales\Model\PriceTier\Query\PriceTierById as PriceTierByIdQuery;
use Ecommerce\Sales\Model\PriceTier\Query\PriceTierByIdentifier as PriceTierByIdentifierQuery;
use Ecommerce\Sales\Model\PriceTier\Query\PriceTierByIdentifiers as PriceTierByIdentifiersQuery;
use Ecommerce\Sales\Model\PriceTier\Query\PriceTierByIds as PriceTierByIdsQuery;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;

/**
 * Repository for PriceTier aggregate.
 *
 * Orchestrates Query → Transform → Handler process.
 * Optionally uses Hydration for entity hydration.
 */
class PriceTierRepository extends EcommerceContext
{
    /**
     * Get PriceTier aggregate.
     *
     * @param PriceTierByIdQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return PriceTierHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getPriceTierById(PriceTierByIdQuery $query, bool $asHandler = true): PriceTierHandler|array|null
    {
        $rootContainer = $this->handle(QueryPriceTierById::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get PriceTier aggregate.
     *
     * @param PriceTierByIdentifierQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return PriceTierHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getPriceTierByIdentifier(
        PriceTierByIdentifierQuery $query,
        bool $asHandler = true
    ): PriceTierHandler|array|null {
        $rootContainer = $this->handle(QueryPriceTierByIdentifier::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get PriceTier aggregate.
     *
     * @param PriceTierByIdentifiersQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return PriceTierHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getPriceTierByIdentifiers(
        PriceTierByIdentifiersQuery $query,
        bool $asHandler = true
    ): PriceTierHandler|array|null {
        $rootContainer = $this->handle(QueryPriceTierByIdentifiers::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get PriceTier aggregate.
     *
     * @param PriceTierByIdsQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return PriceTierHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getPriceTierByIds(PriceTierByIdsQuery $query, bool $asHandler = true): PriceTierHandler|array|null
    {
        $rootContainer = $this->handle(QueryPriceTierByIds::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Build PriceTier aggregate from container data.
     *
     * @param array $rootContainer Root container with root entity data
     * @param bool $asHandler If true, return handler; if false, return array
     * @return PriceTierHandler|array|null
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function buildAggregate(array $rootContainer, bool $asHandler = true): PriceTierHandler|array|null
    {
        $container = $rootContainer;
        $aggregateArray = $this->handle(TransformPriceTier::class)($container);

        if (empty($aggregateArray)) {
            return $asHandler ? null : [];
        }

        if (!$asHandler) {
            return $aggregateArray;
        }

        $hydration = $this->handle(Hydration::class);
        $aggregateEntity = $hydration->hydrateAggregate(
            $this->handle(PriceTierAggregate::class),
            $aggregateArray[0]
        );

        return $this->handle(PriceTierHandler::class, aggregate: $aggregateEntity);
    }

    /**
     * Creates a new empty PriceTier aggregate handler.
     *
     * Use this for CREATE operations where no existing aggregate exists.
     *
     * @return PriceTierHandler Handler with empty aggregate
     * @throws Throwable
     */
    public function createNew(): PriceTierHandler
    {
        $aggregateEntity = $this->handle(PriceTierAggregate::class);

        return $this->handle(PriceTierHandler::class, aggregate: $aggregateEntity);
    }
    /**
     * Persists the PriceTier aggregate.
     *
     * Validates all OWNED entities before persistence.
     *
     * @param PriceTierHandler $handler The aggregate handler
     * @return ContextResponseInterface Response with events
     * @throws Throwable
     */
    public function persist(PriceTierHandler $handler): ContextResponseInterface
    {
        $this->handle(ValidatePriceTier::class)($handler);

        return $this->handle(PersistPriceTier::class)($handler);
    }
}
