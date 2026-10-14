<?php

declare(strict_types=1);

namespace JardisCore\App\Middleware;

use Closure;
use InvalidArgumentException;
use JardisCore\App\Handler\Middleware\BuildProjectMiddleware;
use JardisCore\App\Handler\Middleware\BuildSessionMiddleware;
use JardisCore\App\Handler\Middleware\ResolveTokenStore;
use JardisSupport\Contract\Auth\TokenStoreInterface;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Turns a middleware name into a PSR-15 middleware.
 *
 * Orchestrator over {@see BuildSessionMiddleware} and {@see BuildProjectMiddleware}: the
 * standard name `session` builds {@see RequireSession}, a fully qualified class name builds
 * a project middleware that receives the kernel. Anything else is rejected. The token store
 * is resolved only when `session` is requested; constructing the factory does no work.
 */
final class MiddlewareFactory
{
    private readonly Closure $buildSession;
    private readonly Closure $buildProject;

    public function __construct(
        DomainKernelInterface $kernel,
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?TokenStoreInterface $tokenStore = null,
    ) {
        $psr17 = new Psr17Factory();

        $this->buildSession = (new BuildSessionMiddleware(
            (new ResolveTokenStore($kernel, $tokenStore))->__invoke(...),
            $responseFactory ?? $psr17,
            $streamFactory ?? $psr17,
        ))->__invoke(...);
        $this->buildProject = (new BuildProjectMiddleware($kernel))->__invoke(...);
    }

    /**
     * @throws InvalidArgumentException for an unknown standard name or an unusable class
     * @throws \RuntimeException when a required package or token store is missing
     */
    public function __invoke(string $name): MiddlewareInterface
    {
        return match (true) {
            $name === 'session' => ($this->buildSession)(),
            str_contains($name, '\\') => ($this->buildProject)($name),
            default => throw new InvalidArgumentException(sprintf(
                'Unknown middleware "%s": use the standard name "session" or a fully qualified class name.',
                $name
            )),
        };
    }
}
