<?php

declare(strict_types=1);

namespace JardisCore\App\Middleware;

use Closure;
use InvalidArgumentException;
use JardisCore\App\Handler\Request\ReadRequestAttributeList;
use JardisCore\App\Handler\Response\BuildErrorResponse;
use JardisSupport\Contract\Auth\RequestAttribute;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Answers 403 when the session of the request does not hold the right a route needs.
 *
 * Register it per route, behind the global {@see RequireSession}: that one binds the request to a session
 * (guest -> 401) and sets `RequestAttribute::RIGHTS` from the rights claim of the session; this one only asks
 * whether the one right of the route is among them. It is an orchestrator: it reads the rights list
 * ({@see ReadRequestAttributeList}), asks the injected check, and routes - pass on, or end the request with the
 * canonical envelope ({@see BuildErrorResponse}): `403`, `errors.messageKey = access.rightMissing`,
 * `errors.context.right = <the right of the route>`. The handler is not called on a denial.
 *
 * The check is injected as a closure `fn(list<string> $rights, string $right): bool` - the rights test itself
 * (`resource:action`, `resource:*`, the superuser `*`) is `JardisSupport\Auth\Handler\Authorization\HasRight`
 * (`jardissupport/auth`); this package does not depend on it. Fail-closed: a RIGHTS attribute that is absent
 * (no right granted) or not a list of strings (unusable), and a check that cannot parse a right
 * (`InvalidArgumentException`), all answer 403 - never 200, never 500. A guest never gets here (401 comes first
 * from `RequireSession`); if the route is wired without it, the missing RIGHTS still denies.
 *
 * It does not implement {@see \JardisCore\App\Contract\SessionGuardInterface}, so it never counts as the
 * session guard of `AppConfig::$requireSessionGuard`.
 */
final class RequireRight implements MiddlewareInterface
{
    public const MESSAGE_KEY = 'access.rightMissing';

    private readonly Closure $readRights;
    private readonly Closure $reject;

    /**
     * @param string $right the one right of the route, `resource:action` (the form the check accepts)
     * @param Closure(list<string>, string): bool $hasRight true when the right is among the granted rights
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        private readonly string $right,
        private readonly Closure $hasRight,
    ) {
        if ($right === '') {
            throw new InvalidArgumentException('RequireRight needs a right.');
        }

        $this->readRights = (new ReadRequestAttributeList())->__invoke(...);

        $buildErrorResponse = new BuildErrorResponse($responseFactory, $streamFactory);
        $this->reject = static fn (): ResponseInterface => $buildErrorResponse(
            403,
            [],
            [
                'message' => sprintf('Right "%s" is missing.', $right),
                'messageKey' => self::MESSAGE_KEY,
                'context' => ['right' => $right],
            ],
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $rights = ($this->readRights)($request, RequestAttribute::RIGHTS);
        if ($rights === null) {
            return ($this->reject)();
        }

        try {
            $granted = ($this->hasRight)($rights, $this->right);
        } catch (InvalidArgumentException) {
            return ($this->reject)();
        }

        return $granted ? $handler->handle($request) : ($this->reject)();
    }
}
