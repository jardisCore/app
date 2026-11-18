<?php

declare(strict_types=1);

namespace JardisCore\App\Middleware;

use Closure;
use JardisCore\App\Handler\Request\ReadRequestAttribute;
use JardisCore\App\Handler\Request\ReadRequestAttributeInt;
use JardisCore\App\Handler\Session\ApplyFallbackGroups;
use JardisSupport\Contract\Auth\GroupLookupInterface;
use JardisSupport\Contract\Auth\RequestAttribute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Binds the fallback groups of the information kinds to a request that has no personal group.
 *
 * Orchestrator over three closures - read personal group and tenant, apply the answers - plus the contract call
 * {@see GroupLookupInterface::fallbackGroupOf()}; no logic of its own beyond the routing between them.
 * Register it globally behind {@see ResolveGroups}, which sets `RequestAttribute::PERSONAL_GROUP`. A request
 * with a personal group (an integer) is not asked: the attribute `RequestAttribute::FALLBACK_GROUPS` stays
 * removed. Otherwise the contract is asked once per information kind with the tenant (`TENANT`, empty = `null`);
 * a kind without a fallback group (`null`) falls away, the rest is bound through {@see ApplyFallbackGroups}.
 * The question does not need a subject: an anonymous request is asked as well.
 *
 * A lookup that throws is not caught: the exception ends the request in the existing error path of the
 * pipeline (500), never as a request without fallback groups. Does not implement
 * {@see \JardisCore\App\Contract\SessionGuardInterface}.
 */
final class ResolveFallbackGroups implements MiddlewareInterface
{
    private readonly Closure $readPersonalGroup;
    private readonly Closure $readAttribute;
    private readonly Closure $applyFallbackGroups;

    /**
     * @param list<int> $kinds the information kinds whose fallback group the request may need
     */
    public function __construct(private readonly GroupLookupInterface $lookup, private readonly array $kinds)
    {
        $this->readPersonalGroup = (new ReadRequestAttributeInt())->__invoke(...);
        $this->readAttribute = (new ReadRequestAttribute())->__invoke(...);
        $this->applyFallbackGroups = (new ApplyFallbackGroups())->__invoke(...);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($this->readPersonalGroup)($request, RequestAttribute::PERSONAL_GROUP) !== null) {
            return $handler->handle(($this->applyFallbackGroups)($request, []));
        }

        $tenant = ($this->readAttribute)($request, RequestAttribute::TENANT);
        $answers = [];

        foreach ($this->kinds as $kind) {
            $answers[$kind] = $this->lookup->fallbackGroupOf($kind, $tenant === '' ? null : $tenant);
        }

        return $handler->handle(($this->applyFallbackGroups)($request, $answers));
    }
}
