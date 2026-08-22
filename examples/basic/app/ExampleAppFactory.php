<?php

declare(strict_types=1);

namespace ExampleApp;

use Ecommerce\Ecommerce;
use Ecommerce\Sales\Sales;
use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Routes;
use JardisCore\Kernel\DomainKernel;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use Middlewares\ClientIp;
use Nyholm\Psr7\Factory\Psr17Factory;
use PDO;

/**
 * Builds the wired example App (PLAN P7 §5): Koffer -> Ecommerce domain
 * composition (K8, the Builder-generated equivalent of `App/bootstrap.php`)
 * -> Routes (F2 global middleware + F5 health + the `orders` routes) ->
 * `App`. Shared by `public/index.php` (the getting-started recipe) and
 * `tests/Acceptance` (the same wiring, in-process, plus synthetic routes
 * the AK-Matrix needs and the doc recipe deliberately omits).
 */
final class ExampleAppFactory
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly bool $debug = false,
    ) {
    }

    public function kernel(): DomainKernelInterface
    {
        return new DomainKernel(
            projectRoot: dirname(__DIR__) . '/domain/Ecommerce',
            connection: $this->pdo,
        );
    }

    public function sales(DomainKernelInterface $kernel): Sales
    {
        return (new Ecommerce($kernel))->sales();
    }

    /**
     * @param (callable(Routes): void)|null $extraRoutes registered after the
     *        core routes — e.g. {@see RegisterSyntheticRoutes} in tests.
     */
    public function build(?callable $extraRoutes = null): App
    {
        $kernel = $this->kernel();
        $sales = $this->sales($kernel);

        $routes = new Routes(new Psr17Factory());
        // F2 evidence: a real, small Packagist PSR-15 middleware runs
        // without any Jardis adapter — see RegisterSyntheticRoutes'
        // `/debug/client-ip` for the assertable effect.
        $routes->middleware(new ClientIp());
        $routes->health('/health');

        (new RegisterOrderRoutes())($routes, $sales);

        if ($extraRoutes !== null) {
            $extraRoutes($routes);
        }

        return new App($routes, $kernel, new AppConfig(debug: $this->debug));
    }
}
