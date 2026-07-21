<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support\Helper;

use PDO;

/**
 * Ported from `jardistools/builder` `tests/Builder/PdoFactory.php` — same
 * MYSQL_* ENV convention the shared `mysql` Docker service in
 * `support/docker-compose.yml` already exposes to the `phpcli` container.
 */
final class EcommercePdoFactory
{
    public static function createMySqlPdo(): PDO
    {
        $host = $_ENV['MYSQL_HOST'] ?? 'mysql';
        $port = $_ENV['MYSQL_PORT'] ?? 3306;
        $database = $_ENV['MYSQL_DATABASE'] ?? 'test_db';
        $username = $_ENV['MYSQL_USER'] ?? 'test_user';
        $password = $_ENV['MYSQL_PASSWORD'] ?? 'test_password';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
