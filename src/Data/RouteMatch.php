<?php

declare(strict_types=1);

namespace JardisCore\App\Data;

/**
 * Outcome of a router dispatch (F1/F9) - exactly one of three states,
 * built exclusively through its named constructors:
 *
 * - found(): the Route plus its path parameters.
 * - notFound(): no route matched the requested path.
 * - methodNotAllowed(): the path matched, but not the HTTP method - carries
 *   the full list of methods registered for that path (405 + Allow header;
 *   OPTIONS is never added automatically, E14).
 *
 * Pure data VO - no behaviour beyond access.
 *
 * Path-parameter values are deliberately NOT applied to the ServerRequest as
 * attributes here - the router/dispatch step stays a pure matcher (SRP).
 * Applying `withAttribute()` for each entry of `$parameters` is the
 * documented responsibility of whichever call chain owns both this
 * RouteMatch and the original ServerRequestInterface at the same time (the
 * Pipeline/App orchestrator, P4/P6) - right before the matched route's
 * handler is invoked (F8a).
 */
final readonly class RouteMatch
{
    /**
     * @param array<string, string> $parameters
     * @param list<string> $allowedMethods
     */
    private function __construct(
        public RouteMatchStatus $status,
        public ?Route $route = null,
        public array $parameters = [],
        public array $allowedMethods = [],
    ) {
    }

    /**
     * @param array<string, string> $parameters
     */
    public static function found(Route $route, array $parameters): self
    {
        return new self(RouteMatchStatus::Found, route: $route, parameters: $parameters);
    }

    public static function notFound(): self
    {
        return new self(RouteMatchStatus::NotFound);
    }

    /**
     * @param list<string> $allowedMethods
     */
    public static function methodNotAllowed(array $allowedMethods): self
    {
        return new self(RouteMatchStatus::MethodNotAllowed, allowedMethods: $allowedMethods);
    }
}
