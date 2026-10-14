<?php

declare(strict_types=1);

namespace JardisCore\App;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Registers a single route as anonymous (needs no session). Obtained via
 * `Routes::anonymous()`; every verb delegates to `Routes::register()` with
 * `anonymous: true`. Holds no state besides the Routes reference.
 */
final class AnonymousRegistrar
{
    public function __construct(private readonly Routes $routes)
    {
    }

    public function get(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->routes->register('GET', $path, $handler, $middleware, anonymous: true);
    }

    public function post(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->routes->register('POST', $path, $handler, $middleware, anonymous: true);
    }

    public function put(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->routes->register('PUT', $path, $handler, $middleware, anonymous: true);
    }

    public function patch(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->routes->register('PATCH', $path, $handler, $middleware, anonymous: true);
    }

    public function delete(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->routes->register('DELETE', $path, $handler, $middleware, anonymous: true);
    }
}
