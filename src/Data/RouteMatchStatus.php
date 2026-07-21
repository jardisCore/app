<?php

declare(strict_types=1);

namespace JardisCore\App\Data;

/**
 * The three possible outcomes of a router dispatch (F1/F9): a route matched
 * (Found), no route matched the path at all (NotFound), or the path matched
 * but not the HTTP method (MethodNotAllowed). Pure discriminator for
 * RouteMatch - carries no data of its own.
 */
enum RouteMatchStatus
{
    case Found;
    case NotFound;
    case MethodNotAllowed;
}
