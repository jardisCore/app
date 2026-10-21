<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads a request attribute as a list of strings: the value the session door stored under `$name`
 * (e.g. `RequestAttribute::RIGHTS`) - never client input on its own.
 *
 * - attribute absent (or `null`) -> `[]`;
 * - a list of strings (also an empty one) -> that list;
 * - anything else (a string, a scalar, a list with a non-string element, an associative array) -> `null`,
 *   "unusable": the value is not replaced silently, the caller decides (a route answers 401, never 500).
 *
 * The closure only reads, it never decides and never throws (but `\Error`).
 * {@see ReadRequestAttribute} reads the same attribute as a string.
 */
final class ReadRequestAttributeList
{
    /**
     * @return list<string>|null `null` when the attribute is present but not a list of strings
     */
    public function __invoke(ServerRequestInterface $request, string $name): ?array
    {
        $value = $request->getAttribute($name);

        if ($value === null) {
            return [];
        }

        if (!is_array($value) || !array_is_list($value)) {
            return null;
        }

        foreach ($value as $element) {
            if (!is_string($element)) {
                return null;
            }
        }

        return $value;
    }
}
