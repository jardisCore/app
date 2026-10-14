<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Middleware;

use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Factory\Factory;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;

/**
 * Builds a project middleware from its class name.
 *
 * Convention: the class implements {@see MiddlewareInterface} and has either no
 * constructor or a `__construct(DomainKernelInterface $kernel)`; the kernel is injected.
 */
final class BuildProjectMiddleware
{
    public function __construct(private readonly DomainKernelInterface $kernel)
    {
    }

    public function __invoke(string $class): MiddlewareInterface
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Middleware class "%s" does not exist.', $class));
        }

        if (!is_subclass_of($class, MiddlewareInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('Middleware class "%s" must implement %s.', $class, MiddlewareInterface::class)
            );
        }

        if (!(new ReflectionClass($class))->isInstantiable()) {
            throw new InvalidArgumentException(sprintf('Middleware class "%s" is not instantiable.', $class));
        }

        /** @var MiddlewareInterface $middleware verified above via is_subclass_of() */
        $middleware = (new Factory())->create($class, $this->kernel);

        return $middleware;
    }
}
