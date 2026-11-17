<?php

declare(strict_types=1);

namespace JardisCore\App\Middleware;

use Closure;
use JardisCore\App\Handler\Request\ReadRequestAttribute;
use JardisCore\App\Handler\Session\ApplyGroups;
use JardisSupport\Contract\Auth\GroupLookupInterface;
use JardisSupport\Contract\Auth\RequestAttribute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Binds the groups of the session subject to the request.
 *
 * Orchestrator over two closures - read subject and tenant, apply the groups - plus the one call of the
 * contract {@see GroupLookupInterface::groupsOf()}; no logic of its own beyond the routing between them.
 * Register it behind {@see RequireSession}, which sets `RequestAttribute::SUBJECT_ID` and `TENANT`. With a
 * subject the request carries `RequestAttribute::GROUPS` and `PERSONAL_GROUP` as integers (see
 * {@see ApplyGroups}); the contract is asked exactly once per request, with the bare subject id and the
 * tenant (`null` = no tenant). A request without `SUBJECT_ID` (anonymous route, no session) never reaches the
 * contract and carries neither attribute, even if one was set before.
 *
 * A lookup that throws is not caught: the exception ends the request in the existing error path of the
 * pipeline (500), never as a request without groups. Does not implement
 * {@see \JardisCore\App\Contract\SessionGuardInterface}.
 */
final class ResolveGroups implements MiddlewareInterface
{
    private readonly Closure $readAttribute;
    private readonly Closure $applyGroups;

    public function __construct(private readonly GroupLookupInterface $lookup)
    {
        $this->readAttribute = (new ReadRequestAttribute())->__invoke(...);
        $this->applyGroups = (new ApplyGroups())->__invoke(...);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $subjectId = ($this->readAttribute)($request, RequestAttribute::SUBJECT_ID);

        if ($subjectId === '') {
            return $handler->handle(($this->applyGroups)($request, null));
        }

        $tenant = ($this->readAttribute)($request, RequestAttribute::TENANT);
        $groups = $this->lookup->groupsOf($subjectId, $tenant === '' ? null : $tenant);

        return $handler->handle(($this->applyGroups)($request, $groups));
    }
}
