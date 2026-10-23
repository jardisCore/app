<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment as ShipmentHandler;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentById as ShipmentByIdQuery;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as ShipmentByIdentifierQuery;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifiers as ShipmentByIdentifiersQuery;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIds as ShipmentByIdsQuery;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;

/**
 * Repository for Shipment aggregate.
 *
 * Orchestrates Query → Transform → Handler process.
 * Optionally uses Hydration for entity hydration.
 */
class ShipmentRepository extends EcommerceContext
{
    /**
     * Get Shipment aggregate.
     *
     * @param ShipmentByIdQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return ShipmentHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getShipmentById(ShipmentByIdQuery $query, bool $asHandler = true): ShipmentHandler|array|null
    {
        $rootContainer = $this->handle(QueryShipmentById::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Shipment aggregate.
     *
     * @param ShipmentByIdentifierQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return ShipmentHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getShipmentByIdentifier(
        ShipmentByIdentifierQuery $query,
        bool $asHandler = true
    ): ShipmentHandler|array|null {
        $rootContainer = $this->handle(QueryShipmentByIdentifier::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Shipment aggregate.
     *
     * @param ShipmentByIdentifiersQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return ShipmentHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getShipmentByIdentifiers(
        ShipmentByIdentifiersQuery $query,
        bool $asHandler = true
    ): ShipmentHandler|array|null {
        $rootContainer = $this->handle(QueryShipmentByIdentifiers::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Shipment aggregate.
     *
     * @param ShipmentByIdsQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return ShipmentHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getShipmentByIds(ShipmentByIdsQuery $query, bool $asHandler = true): ShipmentHandler|array|null
    {
        $rootContainer = $this->handle(QueryShipmentByIds::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Build Shipment aggregate from container data.
     *
     * @param array $rootContainer Root container with root entity data
     * @param bool $asHandler If true, return handler; if false, return array
     * @return ShipmentHandler|array|null
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function buildAggregate(array $rootContainer, bool $asHandler = true): ShipmentHandler|array|null
    {
        $container = $this->handle(QueryShipmentAdditions::class)($rootContainer);
        $aggregateArray = $this->handle(TransformShipment::class)($container);

        if (empty($aggregateArray)) {
            return $asHandler ? null : [];
        }

        if (!$asHandler) {
            return $aggregateArray;
        }

        $hydration = $this->handle(Hydration::class);
        $aggregateEntity = $hydration->hydrateAggregate(
            $this->handle(ShipmentAggregate::class),
            $aggregateArray[0]
        );

        return $this->handle(ShipmentHandler::class, aggregate: $aggregateEntity);
    }

    /**
     * Creates a new empty Shipment aggregate handler.
     *
     * Use this for CREATE operations where no existing aggregate exists.
     *
     * @return ShipmentHandler Handler with empty aggregate
     * @throws Throwable
     */
    public function createNew(): ShipmentHandler
    {
        $aggregateEntity = $this->handle(ShipmentAggregate::class);

        return $this->handle(ShipmentHandler::class, aggregate: $aggregateEntity);
    }
    /**
     * Persists the Shipment aggregate.
     *
     * Validates all OWNED entities before persistence.
     *
     * @param ShipmentHandler $handler The aggregate handler
     * @return ContextResponseInterface Response with events
     * @throws Throwable
     */
    public function persist(ShipmentHandler $handler): ContextResponseInterface
    {
        $this->handle(ValidateShipment::class)($handler);

        return $this->handle(PersistShipment::class)($handler);
    }
}
