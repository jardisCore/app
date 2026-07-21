<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Shipment as ShipmentHandler;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentById as ShipmentByIdQuery;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIdentifier as ShipmentByIdentifierQuery;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIds as ShipmentByIdsQuery;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentListFilter;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentTrackingListFilter;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentbyOrderNumber as ShipmentbyOrderNumberQuery;
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
    public function getShipmentByIdentifier(ShipmentByIdentifierQuery $query, bool $asHandler = true): ShipmentHandler|array|null
    {
        $rootContainer = $this->handle(QueryShipmentByIdentifier::class)($query);

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
     * Get Shipment aggregate.
     *
     * @param ShipmentbyOrderNumberQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return ShipmentHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getShipmentbyOrderNumber(ShipmentbyOrderNumberQuery $query, bool $asHandler = true): ShipmentHandler|array|null
    {
        $rootContainer = $this->handle(QueryShipmentbyOrderNumber::class)($query);

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

        return $this->handle(ShipmentHandler::class, $aggregateEntity);
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

        return $this->handle(ShipmentHandler::class, $aggregateEntity);
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

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     */
    protected function listResponse(array $rows, int $limit, int $offset): array
    {
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function shipmentList(ShipmentListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryShipmentList::class)($filter), $filter->limit, $filter->offset);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function shipmentTrackingList(ShipmentTrackingListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryShipmentTrackingList::class)($filter), $filter->limit, $filter->offset);
    }
}
