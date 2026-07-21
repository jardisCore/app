<?php

declare(strict_types=1);

namespace JardisCore\App\Contract;

use JardisCore\App\Data\RouteMatch;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Narrow, app-local routing contract (E3): dispatching a request to a
 * RouteMatch. Registration is a separate concern, handled by the Routes
 * collector - this interface deliberately says nothing about that, and
 * nothing about FastRoute or any other routing engine: the engine is an
 * implementation detail behind this interface, fully swappable.
 */
interface RouterInterface
{
    public function dispatch(ServerRequestInterface $request): RouteMatch;
}
