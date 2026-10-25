<?php

declare(strict_types=1);

namespace JardisCore\App\Data;

/**
 * One door of the session flow (sign in, switch tenant) as the route table knows it:
 * the HTTP method and the path. Immutable value object; the body is not part of the door.
 */
final readonly class SessionDoor
{
    public function __construct(
        public string $method,
        public string $path,
    ) {
    }
}
