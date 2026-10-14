<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Middleware;

use JardisAdapter\DbConnection\Factory\ConnectionFactory;
use JardisSupport\Auth\DbTokenStore;
use JardisSupport\Contract\Auth\TokenStoreInterface;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use PDO;
use RuntimeException;

/**
 * Resolves the token store a session middleware reads from.
 *
 * An explicitly passed store wins. Otherwise the kernel's database connection is used: a
 * connection pool hands its writer to {@see DbTokenStore}, a plain PDO is bridged through
 * `ConnectionFactory::fromPdo()`. Resolving opens no connection.
 */
final class ResolveTokenStore
{
    public function __construct(
        private readonly DomainKernelInterface $kernel,
        private readonly ?TokenStoreInterface $tokenStore = null,
    ) {
    }

    public function __invoke(): TokenStoreInterface
    {
        if ($this->tokenStore !== null) {
            return $this->tokenStore;
        }

        $connection = $this->kernel->dbConnection();

        if ($connection instanceof ConnectionPoolInterface) {
            return new DbTokenStore($connection->getWriter());
        }

        if ($connection instanceof PDO) {
            if (!class_exists(ConnectionFactory::class)) {
                throw new RuntimeException(
                    'Bridging the kernel PDO into a token store requires jardisadapter/dbconnection: '
                    . 'composer require jardisadapter/dbconnection'
                );
            }

            return new DbTokenStore((new ConnectionFactory())->fromPdo($connection));
        }

        throw new RuntimeException(
            'No token store available for the session middleware: pass a TokenStoreInterface '
            . 'to the MiddlewareFactory or configure a database connection in the kernel.'
        );
    }
}
