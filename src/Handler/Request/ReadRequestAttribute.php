<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads a request attribute as a string: the value a middleware (session door,
 * path placeholder) stored under `$name` - never client input on its own.
 *
 * Absent or not a string -> `''`. The closure only reads, it never decides:
 * a route that cannot run without the attribute is registered behind
 * {@see \JardisCore\App\Middleware\RequireRequestAttribute}, which answers 403
 * before the handler runs. For an attribute no middleware can guard up front
 * use {@see ReadRequestAttributeStrict}.
 */
final class ReadRequestAttribute
{
    public function __invoke(ServerRequestInterface $request, string $name): string
    {
        $value = $request->getAttribute($name);

        return is_string($value) ? $value : '';
    }
}
