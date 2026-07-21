<?php

declare(strict_types=1);

namespace JardisCore\App;

use Closure;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Handler\Error\HandleThrowable;
use JardisCore\App\Handler\Pipeline\RunMiddlewarePipeline;
use JardisCore\App\Handler\Request\CreateServerRequest;
use JardisCore\App\Handler\Response\BuildErrorResponse;
use JardisCore\App\Handler\Response\EmitResponse;
use JardisCore\App\Handler\Response\ResolveResponse;
use JardisCore\App\Handler\Routing\ApplyRouteAttributes;
use JardisCore\App\Handler\Routing\DispatchAndRespond;
use JardisCore\App\Handler\Routing\ResolveRouteHandler;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * The App orchestrator (E5): consumes a `Routes` collector exactly once
 * (via a freshly built `Router`, whose dispatcher itself only materializes
 * lazily on first dispatch) and wires the entire request-handling pipeline
 * around it - no logic of its own, only Closure composition
 * (Closure-Orchestrator), immutable after construction.
 *
 * `handle(ServerRequestInterface): ResponseInterface` is a pure function:
 * calling it twice against the very same `App` instance never leaks state
 * from one request into the other (every collaborator is a stateless
 * Closure or an immutable VO). The call chain is exactly the one PLAN P6
 * describes: `HandleThrowable` (outermost, E8/F9) wraps `DispatchAndRespond`
 * (dispatch -> 404/405 boundary responses, or Found -> path-attributes
 * (F8a) -> middleware pipeline (F3/E9) -> route handler, auto-mapped via
 * `ResolveRouteHandler`/`ResolveResponse`, E6).
 *
 * `run(): void` is the one impure entry point: build the request from the
 * SAPI globals (`CreateServerRequest`, E12), `handle()` it, `EmitResponse`
 * it (E13, the fourth F9 error timepoint).
 *
 * PSR-17 factories default to nyholm/psr7 (E12) - swappable via
 * constructor injection, symmetric to how FastRoute stays fully
 * encapsulated behind `Contract\RouterInterface` (E3).
 */
final class App
{
    private readonly Closure $handleThrowable;
    private readonly Closure $dispatchAndRespond;
    private readonly Closure $createServerRequest;
    private readonly Closure $emitResponse;

    public function __construct(
        Routes $routes,
        DomainKernelInterface $kernel,
        AppConfig $config,
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?ServerRequestFactoryInterface $serverRequestFactory = null,
        ?UriFactoryInterface $uriFactory = null,
        ?UploadedFileFactoryInterface $uploadedFileFactory = null,
    ) {
        $psr17 = new Psr17Factory();
        $responseFactory ??= $psr17;
        $streamFactory ??= $psr17;
        $serverRequestFactory ??= $psr17;
        $uriFactory ??= $psr17;
        $uploadedFileFactory ??= $psr17;

        $router = new Router($routes->routes());
        $resolveResponse = (new ResolveResponse($responseFactory, $streamFactory))->__invoke(...);

        $this->dispatchAndRespond = (new DispatchAndRespond(
            $router,
            (new ApplyRouteAttributes())->__invoke(...),
            (new ResolveRouteHandler($resolveResponse))->__invoke(...),
            (new RunMiddlewarePipeline())->__invoke(...),
            (new BuildErrorResponse($responseFactory, $streamFactory))->__invoke(...),
            $routes->globalMiddlewares(),
        ))->__invoke(...);

        $this->handleThrowable = (new HandleThrowable(
            $responseFactory,
            $streamFactory,
            $kernel->logger(),
            $config,
        ))->__invoke(...);

        $this->createServerRequest = (new CreateServerRequest(
            $serverRequestFactory,
            $uriFactory,
            $uploadedFileFactory,
            $streamFactory,
        ))->__invoke(...);

        $this->emitResponse = (new EmitResponse())->__invoke(...);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->handleThrowable)($this->dispatchAndRespond, $request);
    }

    public function run(): void
    {
        $request = ($this->createServerRequest)();
        $response = $this->handle($request);

        ($this->emitResponse)($request, $response);
    }
}
