<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Aggregate;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\ChannelHook;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\Voucher as VoucherAggregate;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\VoucherChannel;
use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Data\Hydration;
use JardisSupport\Data\Identity;
use ReflectionException;
use Throwable;

/**
 * Smart protective layer for Voucher aggregate.
 *
 * Provides business logic methods for aggregate management:
 * - setVoucher() - Set/replace root entity
 * - overlayReadBasis() - Lay the caller's read basis over the root snapshot (CAS)
 *
 * OWNED_ENTITY methods:
 * - setVoucherChannel() - Set/replace voucherChannel
 * - removeVoucherChannel() - Remove voucherChannel
 * - setChannelHook() - Set/replace channelHook via voucher.voucherChannel
 * - removeChannelHook() - Remove channelHook via voucher.voucherChannel
 *
 * Persistence API:
 * - getEntityData() - Raw data for persistence
 * - getRemovedIds() - IDs marked for deletion
 * - isMarkedForDeletion() - Check if aggregate is marked for deletion
 */
class Voucher extends EcommerceContext
{
    private ?VoucherAggregate $aggregate = null;

    /** @var bool Flag to mark entire aggregate for deletion */
    private bool $markedForDeletion = false;

    /** @var array<string, array<int>> Tracks removed entity IDs by entity name */
    private array $removedIds = [
        'voucherChannel' => [],
        'channelHook' => [],
    ];

    public function __construct(
        DomainKernelInterface $domainKernel,
        mixed $payload,
        string $version,
        ?VoucherAggregate $aggregate = null,
        int $depth = 0,
        bool $entry = true
    ) {
        parent::__construct($domainKernel, $payload, $version, $depth, $entry);
        $this->aggregate = $aggregate;
    }

    /**
     * Sets/replaces the root Voucher entity.
     * Pass null to mark entire aggregate for deletion.
     *
     * @param array<string, mixed>|null $data Root entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function setVoucher(?array $data): void
    {
        if ($data === null) {
            $this->remove();
            return;
        }

        $this->getHydration()->apply($this->aggregate, $data);
    }

    /**
     * Lays the read basis of the caller over the root snapshot.
     *
     * The next setVoucher() is diffed against these values, so the
     * persist's expected-values check guards what the caller read, not what
     * was loaded just now. Call it BEFORE setVoucher().
     *
     * @param array<string, mixed> $columns Column => value the caller read
     * @throws ReflectionException
     * @throws Throwable
     */
    public function overlayReadBasis(array $columns): void
    {
        $this->getHydration()->hydrate($this->aggregate, $columns);
    }

    /**
     * Marks the entire aggregate for deletion.
     *
     * When persisted, the entire aggregate (including all OWNED_ENTITY)
     * will be deleted from the database.
     */
    public function remove(): void
    {
        $this->markedForDeletion = true;
    }

    /**
     * Returns a detached copy of the aggregate for reading.
     *
     * The returned object is a deep clone - modifications do NOT
     * affect the original aggregate. Use this for read-only access
     * to aggregate data (e.g., validation, comparison).
     *
     * @return VoucherAggregate Cloned aggregate data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getData(): VoucherAggregate
    {
        /** @var VoucherAggregate */
        return $this->getHydration()->cloneAggregate($this->aggregate);
    }

    /**
     * Returns whether the aggregate is marked for deletion.
     */
    public function isMarkedForDeletion(): bool
    {
        return $this->markedForDeletion;
    }

    /**
     * Sets/replaces the voucherChannel entity.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function setVoucherChannel(array $data): void
    {
        $this->handle(namespace\Action\SetVoucherChannel::class)($this->aggregate, $data);
    }

    /**
     * Removes the voucherChannel entity.
     * Tracks for later DELETE operation.
     * Includes CASCADE deletion of OWNED_ENTITY children.
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function removeVoucherChannel(): ?ResponseStatus
    {
        $removals = $this->handle(namespace\Action\RemoveVoucherChannel::class)($this->aggregate);
        if ($removals instanceof ResponseStatus) {
            return $removals;
        }
        foreach ($removals as [$entityName, $entity]) {
            $this->trackCascadeDeletion($entityName, $entity);
        }
        return null;
    }

    /**
     * Sets/replaces the channelHook entity.
     *
     * @param array<string, mixed> $data Entity data
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function setChannelHook(array $data): ?ResponseStatus
    {
        return $this->handle(namespace\Action\SetChannelHook::class)($this->aggregate, $data);
    }

    /**
     * Removes the channelHook entity.
     * Tracks for later DELETE operation.
     *
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function removeChannelHook(): ?ResponseStatus
    {
        $removals = $this->handle(namespace\Action\RemoveChannelHook::class)($this->aggregate);
        if ($removals instanceof ResponseStatus) {
            return $removals;
        }
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
        return null;
    }

    /**
     * Returns entity data as array for persistence.
     *
     * For new entities returns all values (toArray).
     * For existing entities returns only changed values.
     *
     * @param string $entityName Entity property name
     * @return array{table: string, pkColumn: string, isNew: bool, values: array<string, mixed>,
     *     expected: array<string, mixed>, snapshot: array<string, mixed>, pkValue: int|string|null,
     *     onInserted: \Closure}
     * @throws InvalidArgumentException If entity name is unknown
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getEntityData(string $entityName): array
    {
        $hydration = $this->getHydration();

        [$entity, $table, $pkColumn, $generatedColumns] = match ($entityName) {
            'voucher' => [
                $this->aggregate,
                VoucherAggregate::SOURCE,
                VoucherAggregate::PRIMARY_KEY,
                ['id', 'created_at']
            ],
            'voucherChannel' => [
                $this->aggregate->getVoucherChannel(),
                VoucherChannel::SOURCE,
                VoucherChannel::PRIMARY_KEY,
                ['id']
            ],
            'channelHook' => [
                $this->aggregate->getVoucherChannel()?->getChannelHook(),
                ChannelHook::SOURCE,
                ChannelHook::PRIMARY_KEY,
                ['id']
            ],
            default => throw new InvalidArgumentException("Unknown entity: $entityName"),
        };

        $pkGetter = 'get' . ucfirst($pkColumn);
        $isNew = $entity === null || $entity->$pkGetter() === null;

        if ($isNew && $entity !== null) {
            if ($entityName === 'voucherChannel') {
                $hydration->hydrate($entity, [
                    'identifier' => $this->aggregate->getVoucherChannel()?->getIdentifier()
                        ?? $this->handle(Identity::class)->generateUuid7(),
                    'voucher_id' => $this->aggregate->getId(),
                ]);
            } elseif ($entityName === 'channelHook') {
                $hydration->hydrate($entity, [
                    'channel_id' => $this->aggregate->getVoucherChannel()?->getId(),
                ]);
            }
        }

        $onInserted = function (int|string $id) use ($hydration, $entity, $entityName, $pkColumn): void {
            if ($entity !== null) {
                $hydration->hydrate($entity, [$pkColumn => $id]);
            }
            if ($entityName === 'voucher') {
                $child = $this->aggregate->getVoucherChannel();
                if ($child !== null) {
                    $hydration->hydrate($child, ['voucher_id' => $id]);
                }
            } elseif ($entityName === 'voucherChannel') {
                $child = $this->aggregate->getVoucherChannel()?->getChannelHook();
                if ($child !== null) {
                    $hydration->hydrate($child, ['channel_id' => $id]);
                }
            }
        };

        return [
            'table' => $table,
            'pkColumn' => $pkColumn,
            'isNew' => $isNew,
            'values' => $entity !== null ? ($isNew ? array_filter(
                $hydration->toArray($entity),
                fn($value, $column) => $value !== null || !in_array($column, $generatedColumns, true),
                ARRAY_FILTER_USE_BOTH
            ) : $hydration->getChanges($entity)) : [],
            'expected' => $entity !== null
                ? ($isNew ? [] : array_intersect_key($hydration->getSnapshot($entity), $hydration->getChanges($entity)))
                : [],
            'snapshot' => $entityName === 'voucher' && $entity !== null && !$isNew
                ? $hydration->getSnapshot($entity)
                : [],
            'pkValue' => $entity !== null ? $entity->$pkGetter() : null,
            'onInserted' => $onInserted,
        ];
    }

    /**
     * Helper method to track CASCADE deletion of OWNED_ENTITY children.
     *
     * @param string $entityName Entity property name
     * @param object|null $entity Entity to track with children
     */
    private function trackCascadeDeletion(string $entityName, ?object $entity): void
    {
        if ($entity === null) {
            return;
        }

        $this->trackDeletion($entityName, $entity);

        switch ($entityName) {
            case 'voucherChannel':
                $child = $entity->getChannelHook();
                if ($child !== null) {
                    $this->trackCascadeDeletion('channelHook', $child);
                }
                break;
        }
    }

    /**
     * Gets all removed entity IDs organized by entity name.
     *
     * @return array<string, array<int>>
     */
    public function getRemovedIds(): array
    {
        return $this->removedIds;
    }

    /**
     * Helper method to track entity removal.
     *
     * @param string $entityName Entity property name
     * @param object|null $entity Entity to track
     */
    private function trackDeletion(string $entityName, ?object $entity): void
    {
        if ($entity !== null) {
            $primaryKey = $entity::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $id = $entity->$getter();

            if ($id !== null) {
                $this->removedIds[$entityName][] = $id;
            }
        }
    }

    /**
     * Gets Hydration service (shared via Factory).
     * @throws Throwable
     */
    private function getHydration(): Hydration
    {
        return $this->handle(Hydration::class);
    }
}
