<?php

declare(strict_types=1);

namespace JardisCore\App\Middleware;

use Closure;
use JardisCore\App\Data\ShareTargetSource;
use JardisCore\App\Handler\Request\ApplyShareTargets;
use JardisCore\App\Handler\Request\LookUpShareTargets;
use JardisCore\App\Handler\Request\ReadRequestAttribute;
use JardisCore\App\Handler\Request\ReadShareTargetsFromBody;
use JardisCore\App\Handler\Request\ReadShareTargetsFromHeader;
use JardisSupport\Contract\Auth\GroupLookupInterface;
use JardisSupport\Contract\Auth\RequestAttribute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Binds the share targets of a request ("share with" on create, the share itself) to the request.
 *
 * Orchestrator over closures - read the targets from the source, read the tenant, look the targets up through the
 * contract, apply the attribute; no logic of its own beyond the routing between them. Register it per route behind
 * {@see RequireRight}. The source is chosen by the constructor: {@see ShareTargetSource::Header} (header
 * `X-Jardis-Share-With: 12=view, 15=edit`) or {@see ShareTargetSource::Body} (body fields `groups`, `person`,
 * `level`).
 *
 * `RequestAttribute::SHARE_TARGETS` is then
 * - absent: the request names no targets (also an empty or broken body) - the contract is not asked;
 * - `array<int, string>`: group id => level word, the word unchanged (`''` without one); a person is her personal
 *   group, groups must exist in the tenant (`RequestAttribute::TENANT`, empty = `null`);
 * - `false`: not resolvable (foreign tenant, unknown person or group, unusable input).
 *
 * It never answers on its own and never checks a level word: the handler runs, the door decides. A value set
 * before is removed first. A lookup that throws is not caught: the existing error path answers 500. Does not
 * implement {@see \JardisCore\App\Contract\SessionGuardInterface}.
 */
final class ResolveShareTargets implements MiddlewareInterface
{
    private readonly Closure $readTargets;
    private readonly Closure $readAttribute;
    private readonly Closure $lookUp;
    private readonly Closure $apply;

    public function __construct(GroupLookupInterface $lookup, ShareTargetSource $source)
    {
        $this->readTargets = match ($source) {
            ShareTargetSource::Header => (new ReadShareTargetsFromHeader())->__invoke(...),
            ShareTargetSource::Body => (new ReadShareTargetsFromBody())->__invoke(...),
        };
        $this->readAttribute = (new ReadRequestAttribute())->__invoke(...);
        $this->lookUp = (new LookUpShareTargets($lookup))->__invoke(...);
        $this->apply = (new ApplyShareTargets())->__invoke(...);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $named = ($this->readTargets)($request);

        if ($named === null) {
            return $handler->handle(($this->apply)($request, null));
        }

        if ($named === false) {
            return $handler->handle(($this->apply)($request, false));
        }

        $tenant = ($this->readAttribute)($request, RequestAttribute::TENANT);

        return $handler->handle(($this->apply)($request, ($this->lookUp)($named, $tenant === '' ? null : $tenant)));
    }
}
