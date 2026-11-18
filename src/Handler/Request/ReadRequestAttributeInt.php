<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads a request attribute as an integer: the value a middleware stored under `$name`
 * (e.g. `RequestAttribute::PERSONAL_GROUP`) - never client input on its own.
 *
 * An integer -> that integer; absent or anything else (a string, a float, a list) -> `null`. The closure only
 * reads, it never decides and never throws (but `\Error`). {@see ReadRequestAttribute} reads the same
 * attribute as a string.
 */
final class ReadRequestAttributeInt
{
    public function __invoke(ServerRequestInterface $request, string $name): ?int
    {
        $value = $request->getAttribute($name);

        return is_int($value) ? $value : null;
    }
}
