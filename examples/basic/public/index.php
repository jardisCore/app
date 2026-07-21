<?php

declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

use ExampleApp\ExampleAppFactory;

/**
 * Getting-started recipe (PLAN P7 §5, docs/getting-started.md pattern)
 * wired against the REAL vendored Ecommerce fixture instead of the raw
 * PSR-17 stub in the package docs. Ziel B ("Hello Domain" < 15 min): this
 * file plus `ExampleAppFactory`/`RegisterOrderRoutes` is the whole example.
 *
 * Fatal-error safety net (E8) — same shutdown-function recipe as
 * `docs/getting-started.md`; keep `display_errors=Off` in production so a
 * fatal error never leaks a stack trace to the client.
 */
register_shutdown_function(static function (): void {
    $error = error_get_last();
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    if ($error !== null && in_array($error['type'], $fatal, true)) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        error_log(sprintf('Fatal error: %s in %s:%d', $error['message'], $error['file'], $error['line']));
    }
});

// This example wires the test MySQL service directly (see
// support/docker-compose.yml's `mysql` service, `make start`) instead of
// the full ENV-cascading Bootstrap-Packer — the getting-started doc
// (`docs/getting-started.md`) already covers `BuildDomainKernelFromEnv`;
// this example's own point is the domain wiring (K8) + the `orders`
// routes (F6/F8) against a REAL generated Ecommerce fixture.
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['MYSQL_HOST'] ?? 'mysql',
        $_ENV['MYSQL_PORT'] ?? 3306,
        $_ENV['MYSQL_DATABASE'] ?? 'test_db',
    ),
    $_ENV['MYSQL_USER'] ?? 'test_user',
    $_ENV['MYSQL_PASSWORD'] ?? 'test_password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$debug = (bool) ($_ENV['APP_DEBUG'] ?? false);
$app = (new ExampleAppFactory($pdo, $debug))->build();

$app->run();
