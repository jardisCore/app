<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Query\Handler\GetShipmentByIdHandler;
use Ecommerce\Fulfillment\Model\Shipment\Query\Handler\GetShipmentByIdentifierHandler;
use Ecommerce\Fulfillment\Model\Shipment\Query\Handler\GetShipmentByIdentifiersHandler;
use Ecommerce\Fulfillment\Model\Shipment\Query\Handler\GetShipmentByIdsHandler;
use Ecommerce\Fulfillment\Model\Shipment\Query\Handler\GetShipmentByOrderNumberHandler;
use Ecommerce\Fulfillment\Model\Shipment\Query\Handler\GetShipmentListHandler;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByOrderNumberFilter;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentListFilter;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Data\ReadResult;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentById as QueryShipmentById;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as QueryShipmentByIdentifier;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifiers as QueryShipmentByIdentifiers;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIds as QueryShipmentByIds;

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
     * Gets ShipmentByIdentifiers aggregate.
     *
     * @param QueryShipmentByIdentifiers $shipmentByIdentifiers Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getShipmentByIdentifiers(
        QueryShipmentByIdentifiers $shipmentByIdentifiers,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetShipmentByIdentifiersHandler::class, $shipmentByIdentifiers, $version)();
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
     * Declared query `shipmentByOrderNumber` (Queries.json, visibility public).
     *
     * @param ShipmentByOrderNumberFilter $filter Declared query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function shipmentByOrderNumber(ShipmentByOrderNumberFilter $filter, string $version = ''): ReadResult
    {
        return $this->context(GetShipmentByOrderNumberHandler::class, $filter, $version)();
    }

    /**
     * Declared query `shipmentList` (Queries.json, visibility public).
     *
     * @param ShipmentListFilter $filter Declared query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function shipmentList(ShipmentListFilter $filter, string $version = ''): ReadResult
    {
        return $this->context(GetShipmentListHandler::class, $filter, $version)();
    }
}
