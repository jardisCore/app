<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Acceptance;

use Ecommerce\Ecommerce;
use ExampleApp\ExampleAppFactory;
use ExampleApp\RegisterSyntheticRoutes;
use JardisCore\App\App;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\Helper\EcommercePdoFactory;
use JardisCore\App\Tests\Support\Helper\EcommerceSchemaLoader;
use JardisCore\App\Tests\Support\Helper\SchemaToMysqlDdl;
use Nyholm\Psr7\Factory\Psr17Factory;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base for the P7 e2e Acceptance suite (PLAN P7 §5): the REAL App-Layer
 * pipeline (`App::handle()`, in-process — SAPI itself is already proven by
 * `SapiSmokeTest`, P6) driven against the vendored, generated Ecommerce
 * fixture (`examples/basic/domain/Ecommerce/`) over the real shared MySQL
 * service (`support/docker-compose.yml`, `make start`).
 *
 * DDL setup/teardown mirrors the Builder's own
 * `JardisTools\Tests\Builder\Integration\Ecommerce\EcommerceTestCase`
 * pattern (PLAN "Fixture-Betrieb im Builder") — ported test-support
 * utilities under `tests/Support/Helper/`, not a change to the vendored
 * fixture itself.
 */
abstract class EcommerceAcceptanceTestCase extends TestCase
{
    protected static ?PDO $pdo = null;
    /** @var array{connection: string, tables: array<string, array<string, mixed>>} */
    protected static array $schema = ['connection' => '', 'tables' => []];

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Ecommerce::class)) {
            self::markTestSkipped('Vendored Ecommerce fixture not found under examples/basic/domain/Ecommerce.');
        }

        self::$schema = EcommerceSchemaLoader::load();
        self::$pdo = EcommercePdoFactory::createMySqlPdo();

        foreach (SchemaToMysqlDdl::drop(self::$schema) as $drop) {
            self::$pdo->exec($drop);
        }
        foreach (SchemaToMysqlDdl::generate(self::$schema) as $ddl) {
            self::$pdo->exec($ddl);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = self::$pdo;
        assert($pdo !== null);

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (array_keys(self::$schema['tables']) as $table) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo !== null) {
            foreach (SchemaToMysqlDdl::drop(self::$schema) as $drop) {
                self::$pdo->exec($drop);
            }
        }
        self::$pdo = null;
    }

    /**
     * @param (callable(Routes): void)|null $extraRoutes
     */
    protected function buildApp(?callable $extraRoutes = null): App
    {
        $pdo = self::$pdo;
        assert($pdo !== null);

        return (new ExampleAppFactory($pdo))->build($extraRoutes);
    }

    /**
     * Builds the App including the synthetic test-only routes (204/401/
     * 403/409 + the F2 client-ip demonstration route).
     */
    protected function buildAppWithSyntheticRoutes(): App
    {
        return $this->buildApp((new RegisterSyntheticRoutes())(...));
    }

    protected function jsonRequest(string $method, string $path, mixed $payload): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        $body = $factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR));

        return $factory->createServerRequest($method, $path)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);
    }

    protected function request(string $method, string $path): ServerRequestInterface
    {
        return (new Psr17Factory())->createServerRequest($method, $path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(ResponseInterface $response): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
