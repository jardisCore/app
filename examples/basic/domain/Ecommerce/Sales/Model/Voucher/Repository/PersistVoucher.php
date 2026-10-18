<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\ChannelHook;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\VoucherChannel;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher as VoucherHandler;
use Exception;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\DbConnection\DbConnectionInterface;
use JardisSupport\Contract\DbConnection\SavepointInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Contract\Repository\Exception\UniqueViolationException;
use JardisSupport\Repository\Adapter\PdoConnection;
use JardisSupport\Repository\Repository;
use PDOException;
use Throwable;

/**
 * Persists Voucher with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistVoucher extends EcommerceContext
{
    /**
     * Persists the complete aggregate with transaction management.
     *
     * Uses AggregateHandler public API for data access (no Reflection).
     * Uses Repository for raw data CRUD operations.
     *
     * @param VoucherHandler $handler Aggregate handler with business logic
     * @return ContextResponseInterface Response with events
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     * @throws UniqueViolationException
     */
    public function __invoke(VoucherHandler $handler): ContextResponseInterface
    {
        if ($handler->isMarkedForDeletion()) {
            $this->deleteAggregate($handler);
            return $this->result();
        }

        $writer = $this->resolveWriter();
        $ownsTransaction = !$writer->inTransaction();
        if ($ownsTransaction) {
            $writer->beginTransaction();
        } else {
            $writer->savepoint('jardis_persist_voucher');
        }

        $isNew = false;
        try {
            $isNew = $this->persistVoucher($handler);
            $this->persistVoucherChannel($handler);
            $this->persistChannelHook($handler);

            $this->deleteRemovedEntities($handler);

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_voucher');
            }

            return $this->result();
        } catch (UniqueViolationException $violation) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_voucher');
                $writer->releaseSavepoint('jardis_persist_voucher');
            }
            throw new ConcurrencyConflictException(
                'duplicate',
                'Voucher',
                $this->conflictContext($handler),
                $this->violatedFields($violation)
            );
        } catch (TransientConflictException $transient) {
            if ($ownsTransaction) {
                if ($writer->inTransaction()) {
                    $writer->rollback();
                }
            } else {
                try {
                    $writer->rollbackToSavepoint('jardis_persist_voucher');
                    $writer->releaseSavepoint('jardis_persist_voucher');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_write',
                'Voucher',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_voucher');
                $writer->releaseSavepoint('jardis_persist_voucher');
            }
            throw $e;
        }
    }

    /**
     * Persists voucher entity data.
     *
     * @param VoucherHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistVoucher(VoucherHandler $handler): bool
    {
        $voucherData = $handler->getEntityData('voucher');
        if ($voucherData['isNew']) {
            if (!empty($voucherData['values'])) {
                $newId = $this->getRepository()->insert(
                    $voucherData['table'],
                    $voucherData['pkColumn'],
                    $this->normalizeBoolValues($voucherData['values'])
                );
                ($voucherData['onInserted'])($newId);
            }
        } elseif (!empty($voucherData['values'])) {
            if (
                !$this->getRepository()->update(
                    $voucherData['table'],
                    $voucherData['pkColumn'],
                    $voucherData['pkValue'],
                    $this->normalizeBoolValues($voucherData['values']),
                    $this->normalizeBoolValues($voucherData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Voucher',
                    $this->conflictContext($handler)
                );
            }
        }

        return $voucherData['isNew'];
    }

    /**
     * Persists voucherChannel entity data.
     *
     * @param VoucherHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistVoucherChannel(VoucherHandler $handler): void
    {
        $voucherChannelData = $handler->getEntityData('voucherChannel');
        if ($voucherChannelData['isNew']) {
            if (!empty($voucherChannelData['values'])) {
                $newId = $this->getRepository()->insert(
                    $voucherChannelData['table'],
                    $voucherChannelData['pkColumn'],
                    $this->normalizeBoolValues($voucherChannelData['values'])
                );
                ($voucherChannelData['onInserted'])($newId);
            }
        } elseif (!empty($voucherChannelData['values'])) {
            if (
                !$this->getRepository()->update(
                    $voucherChannelData['table'],
                    $voucherChannelData['pkColumn'],
                    $voucherChannelData['pkValue'],
                    $this->normalizeBoolValues($voucherChannelData['values']),
                    $this->normalizeBoolValues($voucherChannelData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Voucher',
                    $this->conflictContext($handler)
                );
            }
        }
    }

    /**
     * Persists channelHook entity data.
     *
     * @param VoucherHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistChannelHook(VoucherHandler $handler): void
    {
        $channelHookData = $handler->getEntityData('channelHook');
        if ($channelHookData['isNew']) {
            if (!empty($channelHookData['values'])) {
                $newId = $this->getRepository()->insert(
                    $channelHookData['table'],
                    $channelHookData['pkColumn'],
                    $this->normalizeBoolValues($channelHookData['values'])
                );
                ($channelHookData['onInserted'])($newId);
            }
        } elseif (!empty($channelHookData['values'])) {
            if (
                !$this->getRepository()->update(
                    $channelHookData['table'],
                    $channelHookData['pkColumn'],
                    $channelHookData['pkValue'],
                    $this->normalizeBoolValues($channelHookData['values']),
                    $this->normalizeBoolValues($channelHookData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Voucher',
                    $this->conflictContext($handler)
                );
            }
        }
    }

    /**
     * Deletes all tracked OWNED_ENTITY from the database.
     *
     * Delegates to Repository::deleteAll() for batch deletion.
     * Deletes are performed in FK-safe order.
     *
     * @param VoucherHandler $handler Aggregate handler
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function deleteRemovedEntities(VoucherHandler $handler): void
    {
        $removedIds = $handler->getRemovedIds();
        if (!empty($removedIds['channelHook'])) {
            $this->getRepository()->deleteAll(
                ChannelHook::SOURCE,
                ChannelHook::PRIMARY_KEY,
                $removedIds['channelHook']
            );
        }

        if (!empty($removedIds['voucherChannel'])) {
            $this->getRepository()->deleteAll(
                VoucherChannel::SOURCE,
                VoucherChannel::PRIMARY_KEY,
                $removedIds['voucherChannel']
            );
        }
    }

    /**
     * Deletes the entire aggregate from the database.
     *
     * Deletes all entities in FK-safe order.
     *
     * @param VoucherHandler $handler The aggregate handler
     * @throws ConcurrencyConflictException
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     */
    protected function deleteAggregate(VoucherHandler $handler): void
    {
        $writer = $this->resolveWriter();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('voucher');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
        } else {
            $writer->savepoint('jardis_persist_voucher');
        }

        try {
            $channelHookData = $handler->getEntityData('channelHook');
            if ($channelHookData['pkValue'] !== null) {
                $this->getRepository()->delete(
                    ChannelHook::SOURCE,
                    ChannelHook::PRIMARY_KEY,
                    $channelHookData['pkValue']
                );
            }

            $voucherChannelData = $handler->getEntityData('voucherChannel');
            if ($voucherChannelData['pkValue'] !== null) {
                $this->getRepository()->delete(
                    VoucherChannel::SOURCE,
                    VoucherChannel::PRIMARY_KEY,
                    $voucherChannelData['pkValue']
                );
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
                        'Voucher',
                        $this->conflictContext($handler)
                    );
                }
            }

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_voucher');
            }
        } catch (TransientConflictException $transient) {
            if ($ownsTransaction) {
                if ($writer->inTransaction()) {
                    $writer->rollback();
                }
            } else {
                try {
                    $writer->rollbackToSavepoint('jardis_persist_voucher');
                    $writer->releaseSavepoint('jardis_persist_voucher');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_delete',
                'Voucher',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_voucher');
                $writer->releaseSavepoint('jardis_persist_voucher');
            }
            throw $e;
        }
    }

    /**
     * Context of every 409 of this aggregate: the outer-door identifier of the
     * root (`id` only without a public unique key), never FK or child internals.
     *
     * @param VoucherHandler $handler Aggregate handler
     * @return array<string, mixed>
     */
    private function conflictContext(VoucherHandler $handler): array
    {
        return ['voucherCode' => $handler->getData()->getVoucherCode()];
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
            'channel_hooks' => ['target_url' => 'channelHook.targetUrl'],
            'voucher_channels' => ['label' => 'voucherChannel.label'],
            'vouchers' => ['amount' => 'amount', 'note' => 'note', 'voucher_code' => 'voucherCode'],
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
     * Resolves the write connection as a savepoint-capable connection.
     *
     * Handles ConnectionPoolInterface (its writer) and plain PDO (wrapped).
     *
     * @throws \RuntimeException If no database connection is configured or its writer cannot hold savepoints
     */
    protected function resolveWriter(): DbConnectionInterface&SavepointInterface
    {
        $connection = $this->resource()->dbConnection();

        if ($connection instanceof \PDO) {
            return new PdoConnection($connection);
        }

        if ($connection instanceof ConnectionPoolInterface) {
            $writer = $connection->getWriter();

            if ($writer instanceof SavepointInterface) {
                return $writer;
            }

            throw new \RuntimeException('The write connection does not support savepoints');
        }

        throw new \RuntimeException('No database connection configured');
    }

    /**
     * Resolves the database connection to a PDO instance (the writer's own).
     *
     * @throws \RuntimeException If no database connection is configured
     */
    protected function resolveConnection(): \PDO
    {
        return $this->resolveWriter()->pdo();
    }
}
