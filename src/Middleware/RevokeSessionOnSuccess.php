<?php

declare(strict_types=1);

namespace JardisCore\App\Middleware;

use Closure;
use JardisCore\App\Handler\Request\ReadRequestAttribute;
use JardisSupport\Contract\Auth\RequestAttribute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Revokes the session of the request once the route has answered successfully (logout, tenant switch).
 *
 * Register it per route, behind the global {@see RequireSession}: that one binds the request to a session and sets
 * `RequestAttribute::SESSION` (the reference that revokes exactly this session). This one calls the handler first and
 * decides on the answer: a `2xx` status and a non-empty session reference -> the injected `$revoke` is called with the
 * reference; the response is returned unchanged. It is an orchestrator: it reads the reference
 * ({@see ReadRequestAttribute}), calls the closure, routes - no session logic of its own.
 *
 * The revocation is injected as a closure `fn(string $sessionReference): void` - the revocation itself is
 * `JardisSupport\Auth\SessionManager::invalidateByReference()` (`jardissupport/auth`); this package does not depend
 * on it. Not `2xx` (4xx, 5xx) -> nothing is revoked: a failed logout leaves the session as it was. No reference
 * (a request without a session, a result that carries none) -> nothing to revoke. An exception from `$revoke` is not
 * swallowed: it leaves the middleware and reaches the error boundary.
 *
 * Revocation takes effect at the next refresh: the access token already issued stays valid until its `exp`.
 *
 * It does not implement {@see \JardisCore\App\Contract\SessionGuardInterface}, so it never counts as the
 * session guard of `AppConfig::$requireSessionGuard`.
 */
final class RevokeSessionOnSuccess implements MiddlewareInterface
{
    private readonly Closure $readReference;

    /**
     * @param Closure(string): void $revoke revokes the session behind the reference
     */
    public function __construct(private readonly Closure $revoke)
    {
        $this->readReference = (new ReadRequestAttribute())->__invoke(...);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $status = $response->getStatusCode();
        $reference = ($this->readReference)($request, RequestAttribute::SESSION);

        if ($status >= 200 && $status < 300 && $reference !== '') {
            ($this->revoke)($reference);
        }

        return $response;
    }
}
