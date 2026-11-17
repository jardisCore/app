<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads a request attribute as a list of integers: the value the session door stored under `$name`
 * (e.g. `RequestAttribute::GROUPS`) - never client input on its own.
 *
 * - attribute absent (or `null`) -> `[]`;
 * - a list of integers (also an empty one) -> that list;
 * - anything else (a string, a scalar, a list with a non-integer element, an associative array) -> `null`,
 *   "unusable": the value is not replaced silently, the caller decides.
 *
 * The closure only reads, it never decides and never throws (but `\Error`).
 * {@see ReadRequestAttributeList} reads the same attribute as a list of strings.
 */
final class ReadRequestAttributeIntList
{
    /**
     * @return list<int>|null `null` when the attribute is present but not a list of integers
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
            if (!is_int($element)) {
                return null;
            }
        }

        return $value;
    }
}
