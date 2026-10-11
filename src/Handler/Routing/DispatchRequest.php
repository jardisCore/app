<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Routing;

use FastRoute\Dispatcher;
use JardisCore\App\Data\Route;
use JardisCore\App\Data\RouteMatch;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Converts one FastRoute dispatch result into a RouteMatch VO (F1/F9). On
 * Found, delivers the matched Route plus its path parameters, percent-decoded
 * exactly once (rawurldecode: `%40` -> `@`, `%20` -> space, `%2F` -> `/` inside
 * the value, `+` stays `+`). Decoding happens here, after matching, because
 * PSR-7 `getPath()` is percent-encoded and FastRoute does not decode - so the
 * match itself runs on the encoded path (a `%2F` never splits a segment) and
 * every consumer of RouteMatch::$parameters sees decoded values. Applying
 * them as request attributes (F8a) is a deliberately separate
 * responsibility of the Pipeline/App call chain (P4/P6), the first place
 * that holds both the RouteMatch and the ServerRequestInterface at once;
 * DispatchRequest itself stays a pure matcher (SRP). On
 * MethodNotAllowed, delivers the full allowed-methods list for the path
 * (OPTIONS is never added automatically, E14).
 */
final class DispatchRequest
{
    public function __invoke(Dispatcher $dispatcher, ServerRequestInterface $request): RouteMatch
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        $result = $dispatcher->dispatch($method, $path);

        if ($result[0] === Dispatcher::FOUND) {
            /** @var Route $route */
            $route = $result[1];
            /** @var array<string, string> $parameters */
            $parameters = $result[2];

            return RouteMatch::found($route, array_map(rawurldecode(...), $parameters));
        }

        if ($result[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @var list<string> $allowedMethods */
            $allowedMethods = $result[1];

            return RouteMatch::methodNotAllowed($allowedMethods);
        }

        return RouteMatch::notFound();
    }
}
