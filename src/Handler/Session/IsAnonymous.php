<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Session;

use JardisCore\App\Handler\Routing\DispatchAndRespond;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Decides whether a request may pass without a session.
 *
 * Anonymous are (1) requests on a route the app itself marked anonymous
 * (`Routes::health()`, signalled by the strict-bool request attribute
 * `DispatchAndRespond::ANONYMOUS_ATTRIBUTE`) and (2) requests whose path matches one
 * of the declared patterns. A pattern is an exact path (`/login`) or, when it ends in
 * `*`, a prefix (`/public/*`). The path is compared as the router sees it (percent-
 * encoded, no normalisation); the HTTP method plays no role.
 */
final class IsAnonymous
{
    /**
     * @param list<string> $paths
     */
    public function __construct(private readonly array $paths = [])
    {
    }

    public function __invoke(ServerRequestInterface $request): bool
    {
        if ($request->getAttribute(DispatchAndRespond::ANONYMOUS_ATTRIBUTE) === true) {
            return true;
        }

        $path = $request->getUri()->getPath();

        foreach ($this->paths as $pattern) {
            if ($this->matches($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    private function matches(string $pattern, string $path): bool
    {
        if (str_ends_with($pattern, '*')) {
            return str_starts_with($path, substr($pattern, 0, -1));
        }

        return $pattern === $path;
    }
}
