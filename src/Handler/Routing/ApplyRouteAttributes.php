<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Routing;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Applies a matched route's raw path parameters (F1) as request attributes
 * (F8a) - the documented call site for the responsibility RouteMatch and
 * DispatchRequest deliberately leave open (see their docblocks): the first
 * point in the call chain that holds both a RouteMatch and the original
 * ServerRequestInterface at once, right before the matched route's handler
 * runs.
 */
final class ApplyRouteAttributes
{
    /**
     * @param array<string, string> $parameters
     */
    public function __invoke(ServerRequestInterface $request, array $parameters): ServerRequestInterface
    {
        foreach ($parameters as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $request;
    }
}
