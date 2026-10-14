<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use JardisCore\Kernel\DomainKernel;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use PDO;

/**
 * Builds a real DomainKernel around a settable database connection.
 */
final class SettableKernel
{
    public static function with(ConnectionPoolInterface|PDO|null $connection = null): DomainKernel
    {
        return new DomainKernel(projectRoot: '/tmp/jardiscore-app-test', connection: $connection);
    }
}
