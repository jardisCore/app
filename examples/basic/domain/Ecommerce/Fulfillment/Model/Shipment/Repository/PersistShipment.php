<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Entity\ShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Entity\TrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment as ShipmentHandler;
use Exception;
use JardisSupport\Contract\DbConnection\DbConnectionInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Contract\Repository\Exception\UniqueViolationException;
use JardisSupport\Repository\Exception\TransactionLostException;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Repository\Handler\Transaction\RunInTransaction;
use JardisSupport\Repository\Repository;
use Throwable;

/**
 * Persists Shipment with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistShipment extends EcommerceContext
{
    private ?RunInTransaction $transactionBracket = null;
    private ?ResolveConnection $connectionResolver = null;

    /**
     * Persists the complete aggregate with transaction management.
     *
     * Uses AggregateHandler public API for data access (no Reflection).
     * Uses Repository for raw data CRUD operations.
     *
     * @param ShipmentHandler $handler Aggregate handler with business logic
     * @return ContextResponseInterface Response with events
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws \RuntimeException
     * @throws Throwable
     * @throws TransactionLostException
     * @throws TransientConflictException
     * @throws UniqueViolationException
     */
    public function __invoke(ShipmentHandler $handler): ContextResponseInterface
    {
        if ($handler->isMarkedForDeletion()) {
            $this->deleteAggregate($handler);
            return $this->result();
        }

        $write = function () use ($handler): void {
            $this->persistShipmentAddress($handler);
            $this->persistShipment($handler);
            $this->persistShipmentItem($handler);
            $this->persistTrackingEvent($handler);

            $this->deleteRemovedEntities($handler);
        };

        try {
            ($this->transaction())(
                $this->writer(),
                'jardis_persist_shipment',
                $write,
                nested: false
            );

            return $this->result();
        } catch (TransactionLostException $lost) {
            throw $lost;
        } catch (UniqueViolationException $violation) {
            throw new ConcurrencyConflictException(
                'duplicate',
                'Shipment',
                $this->conflictContext($handler),
                $this->violatedFields($violation)
            );
        } catch (TransientConflictException $transient) {
            throw new ConcurrencyConflictException(
                'stale_write',
                'Shipment',
                $this->conflictContext($handler)
            );
        }
    }

    /**
     * Persists shipmentAddress entity data.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistShipmentAddress(ShipmentHandler $handler): void
    {
        $shipmentAddressData = $handler->getEntityData('shipmentAddress');
        if ($shipmentAddressData['isNew']) {
            if (!empty($shipmentAddressData['values'])) {
                $newId = $this->getRepository()->insert(
                    $shipmentAddressData['table'],
                    $shipmentAddressData['pkColumn'],
                    $this->normalizeBoolValues($shipmentAddressData['values'])
                );
                ($shipmentAddressData['onInserted'])($newId);
            }
        } elseif (!empty($shipmentAddressData['values'])) {
            if (
                !$this->getRepository()->update(
                    $shipmentAddressData['table'],
                    $shipmentAddressData['pkColumn'],
                    $shipmentAddressData['pkValue'],
                    $this->normalizeBoolValues($shipmentAddressData['values']),
                    $this->normalizeBoolValues($shipmentAddressData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Shipment',
                    $this->conflictContext($handler)
                );
            }
        }
    }

    /**
     * Persists shipment entity data.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistShipment(ShipmentHandler $handler): bool
    {
        $shipmentData = $handler->getEntityData('shipment');
        if ($shipmentData['isNew']) {
            if (!empty($shipmentData['values'])) {
                $newId = $this->getRepository()->insert(
                    $shipmentData['table'],
                    $shipmentData['pkColumn'],
                    $this->normalizeBoolValues($shipmentData['values'])
                );
                ($shipmentData['onInserted'])($newId);
            }
        } elseif (!empty($shipmentData['values'])) {
            if (
                !$this->getRepository()->update(
                    $shipmentData['table'],
                    $shipmentData['pkColumn'],
                    $shipmentData['pkValue'],
                    $this->normalizeBoolValues($shipmentData['values']),
                    $this->normalizeBoolValues($shipmentData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Shipment',
                    $this->conflictContext($handler)
                );
            }
        }

        return $shipmentData['isNew'];
    }

    /**
     * Persists shipmentItem collection data.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistShipmentItem(ShipmentHandler $handler): void
    {
        foreach ($handler->getCollectionData('shipmentItem') as $shipmentItemData) {
            if ($shipmentItemData['isNew']) {
                if (!empty($shipmentItemData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $shipmentItemData['table'],
                        $shipmentItemData['pkColumn'],
                        $this->normalizeBoolValues($shipmentItemData['values'])
                    );
                    ($shipmentItemData['onInserted'])($newId);
                }
            } elseif (!empty($shipmentItemData['values'])) {
                if (
                    !$this->getRepository()->update(
                        $shipmentItemData['table'],
                        $shipmentItemData['pkColumn'],
                        $shipmentItemData['pkValue'],
                        $this->normalizeBoolValues($shipmentItemData['values']),
                        $this->normalizeBoolValues($shipmentItemData['expected'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_write',
                        'Shipment',
                        $this->conflictContext($handler)
                    );
                }
            }
        }
    }

    /**
     * Persists trackingEvent collection data.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistTrackingEvent(ShipmentHandler $handler): void
    {
        foreach ($handler->getCollectionData('trackingEvent') as $trackingEventData) {
            if ($trackingEventData['isNew']) {
                if (!empty($trackingEventData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $trackingEventData['table'],
                        $trackingEventData['pkColumn'],
                        $this->normalizeBoolValues($trackingEventData['values'])
                    );
                    ($trackingEventData['onInserted'])($newId);
                }
            } elseif (!empty($trackingEventData['values'])) {
                if (
                    !$this->getRepository()->update(
                        $trackingEventData['table'],
                        $trackingEventData['pkColumn'],
                        $trackingEventData['pkValue'],
                        $this->normalizeBoolValues($trackingEventData['values']),
                        $this->normalizeBoolValues($trackingEventData['expected'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_write',
                        'Shipment',
                        $this->conflictContext($handler)
                    );
                }
            }
        }
    }

    /**
     * Deletes all tracked OWNED_ENTITY from the database.
     *
     * Delegates to Repository::deleteAll() for batch deletion.
     * Deletes are performed in FK-safe order.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function deleteRemovedEntities(ShipmentHandler $handler): void
    {
        $removedIds = $handler->getRemovedIds();
        if (!empty($removedIds['shipmentItem'])) {
            $this->getRepository()->deleteAll(
                ShipmentItem::SOURCE,
                ShipmentItem::PRIMARY_KEY,
                $removedIds['shipmentItem']
            );
        }

        if (!empty($removedIds['trackingEvent'])) {
            $this->getRepository()->deleteAll(
                TrackingEvent::SOURCE,
                TrackingEvent::PRIMARY_KEY,
                $removedIds['trackingEvent']
            );
        }
    }

    /**
     * Deletes the entire aggregate from the database.
     *
     * Deletes all entities in FK-safe order.
     *
     * @param ShipmentHandler $handler The aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     * @throws TransactionLostException
     * @throws TransientConflictException
     */
    protected function deleteAggregate(ShipmentHandler $handler): void
    {
        $rootData = $handler->getEntityData('shipment');
        $aggregateId = $rootData['pkValue'];

        $write = function () use ($handler, $rootData, $aggregateId): void {
            $shipmentItemData = $handler->getCollectionData('shipmentItem');
            $shipmentItemIds = array_unique(array_filter(array_column($shipmentItemData, 'pkValue')));
            if (!empty($shipmentItemIds)) {
                $this->getRepository()->deleteAll(ShipmentItem::SOURCE, ShipmentItem::PRIMARY_KEY, $shipmentItemIds);
            }

            $trackingEventData = $handler->getCollectionData('trackingEvent');
            $trackingEventIds = array_unique(array_filter(array_column($trackingEventData, 'pkValue')));
            if (!empty($trackingEventIds)) {
                $this->getRepository()->deleteAll(TrackingEvent::SOURCE, TrackingEvent::PRIMARY_KEY, $trackingEventIds);
            }

            if ($aggregateId !== null) {
                if (
                    !$this->getRepository()->delete(
                        $rootData['table'],
                        $rootData['pkColumn'],
                        $aggregateId,
                        $this->normalizeBoolValues($rootData['snapshot'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_delete',
                        'Shipment',
                        $this->conflictContext($handler)
                    );
                }
            }
        };

        try {
            ($this->transaction())(
                $this->writer(),
                'jardis_persist_shipment',
                $write,
                nested: false
            );
        } catch (TransactionLostException $lost) {
            throw $lost;
        } catch (TransientConflictException $transient) {
            throw new ConcurrencyConflictException(
                'stale_delete',
                'Shipment',
                $this->conflictContext($handler)
            );
        }
    }

    /**
     * Context of every 409 of this aggregate: the outer-door identifier of the
     * root (`id` only without a public unique key), never FK or child internals.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @return array<string, mixed>
     */
    private function conflictContext(ShipmentHandler $handler): array
    {
        return ['identifier' => $handler->getData()->getIdentifier()];
    }

    /**
     * Outer-door names of the fields a unique violation hit (`violated` of the 409
     * `duplicate`): root fields by name, child fields as `<kindProperty>.<field>`;
     * columns without an outer name are left out, unknown columns/table give [].
     *
     * @return list<string>
     */
    private function violatedFields(UniqueViolationException $e): array
    {
        $names = match ($e->getTable()) {
            'shipment_addresses' => [
                'city' => 'shipmentAddress.city',
                'company' => 'shipmentAddress.company',
                'country' => 'shipmentAddress.country',
                'phone' => 'shipmentAddress.phone',
                'postal_code' => 'shipmentAddress.postalCode',
                'recipient_name' => 'shipmentAddress.recipientName',
                'state' => 'shipmentAddress.state',
                'street' => 'shipmentAddress.street',
                'street2' => 'shipmentAddress.street2',
            ],
            'shipment_items' => [
                'is_fragile' => 'shipmentItem.isFragile',
                'lot_number' => 'shipmentItem.lotNumber',
                'product_name' => 'shipmentItem.productName',
                'product_sku' => 'shipmentItem.productSku',
                'quantity' => 'shipmentItem.quantity',
                'serial_number' => 'shipmentItem.serialNumber',
                'weight_grams' => 'shipmentItem.weightGrams',
            ],
            'shipments' => [
                'carrier' => 'carrier',
                'customer_identifier' => 'customerIdentifier',
                'delivered_at' => 'deliveredAt',
                'estimated_delivery' => 'estimatedDelivery',
                'identifier' => 'identifier',
                'insurance_value' => 'insuranceValue',
                'note' => 'note',
                'order_number' => 'orderNumber',
                'package_count' => 'packageCount',
                'service_level' => 'serviceLevel',
                'shipped_at' => 'shippedAt',
                'status' => 'status',
                'tracking_number' => 'trackingNumber',
                'weight_grams' => 'weightGrams',
            ],
            'tracking_events' => [
                'detail' => 'trackingEvent.detail',
                'event_code' => 'trackingEvent.eventCode',
                'location' => 'trackingEvent.location',
                'occurred_at' => 'trackingEvent.occurredAt',
                'postal_code' => 'trackingEvent.postalCode',
                'reported_at' => 'trackingEvent.reportedAt',
                'status' => 'trackingEvent.status',
            ],
            default => [],
        };

        $fields = [];
        foreach ($e->getColumns() as $column) {
            if (isset($names[$column])) {
                $fields[] = $names[$column];
            }
        }

        return $fields;
    }

    /**
     * Gets Repository service (shared via Factory).
     *
     * @return Repository
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function getRepository(): Repository
    {
        return $this->handle(Repository::class, $this->resolveConnection());
    }

    /**
     * Casts PHP bool values to int (1/0) for database persistence.
     *
     * PDO binds PHP false as empty string by default, which MySQL rejects for
     * integer columns. This ensures bool values are stored as 0 or 1.
     *
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function normalizeBoolValues(array $values): array
    {
        return array_map(static fn($v) => is_bool($v) ? (int) $v : $v, $values);
    }

    /**
     * The transaction bracket of the repository package (bound once per instance).
     */
    protected function transaction(): RunInTransaction
    {
        return $this->transactionBracket ??= new RunInTransaction();
    }

    /**
     * Resolves the write connection from what the kernel holds: the writer of a
     * connection pool, a plain PDO wrapped, null without a database (the bracket
     * answers that with a configuration error).
     */
    protected function writer(): ?DbConnectionInterface
    {
        $this->connectionResolver ??= new ResolveConnection();

        return ($this->connectionResolver)($this->resource()->dbConnection());
    }

    /**
     * Resolves the database connection to a PDO instance (the writer's own).
     *
     * @throws \RuntimeException If no database connection is configured
     */
    protected function resolveConnection(): \PDO
    {
        $writer = $this->writer();
        if ($writer === null) {
            throw new \RuntimeException('No database connection configured');
        }

        return $writer->pdo();
    }
}
