<?php

declare(strict_types=1);

namespace JardisCore\App;

use JardisCore\App\Data\Route;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Registration collector (E5): accumulates Route VOs and global middleware
 * for later, one-time consumption by Router/App - the route table itself
 * belongs to the Router afterwards (BuildDispatcher consumes this
 * collection exactly once). Holds no request-dispatch behaviour of its own.
 *
 * Registration accepts `callable|RequestHandlerInterface` and converts a
 * callable to a Closure immediately (E4) via first-class callable syntax,
 * so every collected Route already satisfies `Closure|RequestHandlerInterface`.
 *
 * Each verb method also accepts a variadic, trailing list of
 * route-specific `MiddlewareInterface` instances - collected in
 * registration order onto the resulting Route (F3); RunMiddlewarePipeline
 * (P4) is what actually builds and runs the PSR-15 chain from a route's
 * middleware plus the global list.
 */
final class Routes
{
    /** @var list<Route> */
    private array $routes = [];

    /** @var list<MiddlewareInterface> */
    private array $globalMiddleware = [];

    /**
     * @param ResponseFactoryInterface $responseFactory used exclusively by
     *        health() to build its internal 200 response (E12-symmetric
     *        constructor injection - never a hardwired concrete PSR-17
     *        implementation such as nyholm inside this file)
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function get(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->register('GET', $path, $handler, ...$middleware);
    }

    public function post(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->register('POST', $path, $handler, ...$middleware);
    }

    public function put(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->register('PUT', $path, $handler, ...$middleware);
    }

    public function patch(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->register('PATCH', $path, $handler, ...$middleware);
    }

    public function delete(
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $this->register('DELETE', $path, $handler, ...$middleware);
    }

    public function middleware(MiddlewareInterface $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Registers an internal health-check GET route (E10, M10): responds 200
     * with `{"status":200}` without touching any domain.
     */
    public function health(string $path): void
    {
        $responseFactory = $this->responseFactory;

        $this->register('GET', $path, static function () use ($responseFactory): ResponseInterface {
            $response = $responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write('{"status":200}');

            return $response;
        });
    }

    private function register(
        string $method,
        string $path,
        callable|RequestHandlerInterface $handler,
        MiddlewareInterface ...$middleware,
    ): void {
        $resolvedHandler = $handler instanceof RequestHandlerInterface ? $handler : $handler(...);

        $this->routes[] = new Route($method, $path, $resolvedHandler, array_values($middleware));
    }

    /**
     * @return list<Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @return list<MiddlewareInterface>
     */
    public function globalMiddlewares(): array
    {
        return $this->globalMiddleware;
    }
}
