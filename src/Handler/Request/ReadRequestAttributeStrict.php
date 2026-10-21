<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use JardisCore\App\Exception\RequestAttributeMissing;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads a request attribute as a string and fails closed: for an attribute NO
 * middleware guards (read only by a union alternative or an optional/nullable
 * property, so the door cannot require it up front).
 *
 * Absent, not a string or empty -> {@see RequestAttributeMissing} (a
 * `RuntimeException`); the error boundary answers it as the generic 500, so the
 * route never reads or writes under an empty scope.
 */
final class ReadRequestAttributeStrict
{
    /**
     * @throws RequestAttributeMissing
     */
    public function __invoke(ServerRequestInterface $request, string $name): string
    {
        $value = $request->getAttribute($name);
        if (!is_string($value) || $value === '') {
            throw new RequestAttributeMissing(
                'Request attribute ' . $name . ' is not set - the scoped route needs the session middleware.',
            );
        }

        return $value;
    }
}
