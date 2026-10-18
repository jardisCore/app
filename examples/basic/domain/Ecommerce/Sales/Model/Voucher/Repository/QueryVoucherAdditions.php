<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Repository;

use Ecommerce\EcommerceContext;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\DbQuery\DbQueryBuilderInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use Throwable;
use Ecommerce\Sales\Entity\ChannelHook as ChannelHookEntity;
use Ecommerce\Sales\Entity\VoucherChannel as VoucherChannelEntity;

/**
 * Additions query class for Voucher.
 *
 * Loads follow-up entities based on root container data.
 * Called by both root and FK query contexts.
 * Generated code - do not modify directly.
 */
class QueryVoucherAdditions extends EcommerceContext
{
    protected array $container = [];

    /**
     * Load follow-up entities based on root container.
     *
     * @param array $rootContainer Container with root entity data
     * @return array<string, array<int, object>>
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(array $rootContainer = []): array
    {
        $this->container = $rootContainer;
        $this->container['voucherChannel'] = $this->load(
            $this->voucherChannel()
        );
        $this->container['channelHook'] = $this->load(
            $this->channelHook()
        );

        return $this->container;
    }

    /**
     * Query for voucherChannel.
     * @throws Throwable
     */
    protected function voucherChannel(): DbQueryBuilderInterface
    {
        return $this->query(VoucherChannelEntity::class)
            ->where('voucher_id')->in($this->values('voucher', 'id'));
    }

    /**
     * Query for channelHook.
     * @throws Throwable
     */
    protected function channelHook(): DbQueryBuilderInterface
    {
        return $this->query(ChannelHookEntity::class)
            ->where('channel_id')->in($this->values('voucherChannel', 'id'));
    }

    /**
     * Build a new query for an entity class.
     *
     * @param string $entityClass Entity class name
     * @param string|null $alias Optional table alias
     * @return DbQueryBuilderInterface
     * @throws Throwable
     */
    protected function query(
        string $entityClass,
        ?string $alias = null
    ): DbQueryBuilderInterface {
        return $this->handle(DbQuery::class)
            ->from($entityClass::SOURCE, $alias);
    }

    /**
     * Execute query and return results.
     *
     * @param DbQueryBuilderInterface|null $query Query to execute
     * @return array The query results
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function load(?DbQueryBuilderInterface $query): array
    {
        if (!$query instanceof DbQueryBuilderInterface) {
            return [];
        }

        return $this->handle(Repository::class, $this->resolveConnection())->findByQuery($query);
    }

    /**
     * Resolves the database connection to a PDO instance.
     *
     * Handles ConnectionPoolInterface (read/write splitting) and plain PDO.
     *
     * @throws \RuntimeException If no database connection is configured
     */
    protected function resolveConnection(): \PDO
    {
        $connection = $this->resource()->dbConnection();

        if ($connection instanceof \PDO) {
            return $connection;
        }

        if ($connection instanceof ConnectionPoolInterface) {
            return $connection->getReader()->pdo();
        }

        throw new \RuntimeException('No database connection configured');
    }
    /**
     * Get values from loaded container.
     *
     * @param string $parent Parent container name
     * @param string $key Field key to extract
     * @return array Array of values (empty if not found)
     */
    protected function values(string $parent, string $key): array
    {
        if (!isset($this->container[$parent])) {
            return [];
        }

        $result = [];
        foreach ($this->container[$parent] as $row) {
            if (isset($row[$key])) {
                $result[] = $row[$key];
            }
        }

        return array_unique($result);
    }
}
