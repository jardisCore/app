<?php

declare(strict_types=1);

namespace JardisCore\App\Contract;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Marker for a middleware that binds every request to a verified session or rejects it.
 *
 * Carries no methods of its own. It lets the delivery layer recognise a session guard in
 * a middleware list without knowing the concrete class.
 */
interface SessionGuardInterface extends MiddlewareInterface
{
}
