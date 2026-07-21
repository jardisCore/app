<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\Handler\GetShipmentByIdHandler;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\Handler\GetShipmentByIdentifierHandler;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\Handler\GetShipmentByIdsHandler;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\Handler\GetShipmentListHandler;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\Handler\GetShipmentTrackingListHandler;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\Handler\GetShipmentbyOrderNumberHandler;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentListFilter;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentTrackingListFilter;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentById as QueryShipmentById;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIdentifier as QueryShipmentByIdentifier;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIds as QueryShipmentByIds;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentbyOrderNumber as QueryShipmentbyOrderNumber;

/**
 * Shipment Aggregate Read Facade.
 *
 * Thin read-only delegator (G9) — hosts the inline query/list
 * operations for this aggregate. No write access; see the sibling
 * write facade in the same directory for commands + event().
 */
class ShipmentRead extends EcommerceContext
{
    /**
     * Gets ShipmentById aggregate.
     *
     * @param QueryShipmentById $shipmentById Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getShipmentById(QueryShipmentById $shipmentById, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetShipmentByIdHandler::class, $shipmentById, $version)();
    }

    /**
     * Gets ShipmentByIdentifier aggregate.
     *
     * @param QueryShipmentByIdentifier $shipmentByIdentifier Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getShipmentByIdentifier(
        QueryShipmentByIdentifier $shipmentByIdentifier,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetShipmentByIdentifierHandler::class, $shipmentByIdentifier, $version)();
    }

    /**
     * Gets ShipmentByIds aggregate.
     *
     * @param QueryShipmentByIds $shipmentByIds Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getShipmentByIds(QueryShipmentByIds $shipmentByIds, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetShipmentByIdsHandler::class, $shipmentByIds, $version)();
    }

    /**
     * Gets ShipmentbyOrderNumber aggregate.
     *
     * @param QueryShipmentbyOrderNumber $shipmentbyOrderNumber Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getShipmentbyOrderNumber(
        QueryShipmentbyOrderNumber $shipmentbyOrderNumber,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetShipmentbyOrderNumberHandler::class, $shipmentbyOrderNumber, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function shipmentList(ShipmentListFilter $filter, string $version = ''): array
    {
        return $this->context(GetShipmentListHandler::class, $filter, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function shipmentTrackingList(ShipmentTrackingListFilter $filter, string $version = ''): array
    {
        return $this->context(GetShipmentTrackingListHandler::class, $filter, $version)();
    }
}
