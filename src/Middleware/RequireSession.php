<?php

declare(strict_types=1);

namespace JardisCore\App\Middleware;

use Closure;
use JardisCore\App\Handler\Response\BuildErrorResponse;
use JardisCore\App\Handler\Session\ApplySession;
use JardisCore\App\Handler\Session\AuthenticateToken;
use JardisCore\App\Handler\Session\IsAnonymous;
use JardisCore\App\Handler\Session\ReadBearerToken;
use JardisSupport\Contract\Auth\AuthenticatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Binds every request to a verified session or rejects it with 401.
 *
 * Orchestrator over four closures - read the token, authenticate it, bind the session
 * to the request, build the 401 - without logic of its own beyond the routing between
 * them. Anonymous requests (see {@see IsAnonymous}) pass without a session; every other
 * request that reaches the pipeline needs a token the authenticator accepts, otherwise
 * the handler is never called and the canonical envelope answers 401
 * (`{status:401,data:{},errors:{message},meta:{}}`, `WWW-Authenticate: Bearer`). A missing,
 * malformed, unknown, expired or revoked token is answered identically.
 *
 * With a valid session the request carries `RequestAttribute::SUBJECT` and, if the
 * session has a tenant, `RequestAttribute::TENANT` (see {@see ApplySession}).
 *
 * Register as global middleware (`Routes::middleware()`) or per route. As the pipeline only
 * runs on a matched route, unknown paths stay 404 and wrong methods 405.
 */
final class RequireSession implements MiddlewareInterface
{
    private readonly Closure $isAnonymous;
    private readonly Closure $readToken;
    private readonly Closure $authenticate;
    private readonly Closure $applySession;
    private readonly Closure $reject;

    /**
     * @param Closure(string): \JardisSupport\Contract\Auth\CredentialInterface $credentialFor
     *        token string to credential, e.g. `\JardisSupport\Auth\Data\Credential::token(...)`
     * @param list<string> $anonymousPaths exact paths or `prefix*` patterns that need no session
     * @param string $headerName header carrying the token
     * @param string $scheme auth scheme in front of the token ('' = the whole header value is the token)
     */
    public function __construct(
        AuthenticatorInterface $authenticator,
        Closure $credentialFor,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        array $anonymousPaths = [],
        string $headerName = 'Authorization',
        string $scheme = 'Bearer',
    ) {
        $this->isAnonymous = (new IsAnonymous($anonymousPaths))->__invoke(...);
        $this->readToken = (new ReadBearerToken($headerName, $scheme))->__invoke(...);
        $this->authenticate = (new AuthenticateToken($authenticator, $credentialFor))->__invoke(...);
        $this->applySession = (new ApplySession())->__invoke(...);

        $buildErrorResponse = new BuildErrorResponse($responseFactory, $streamFactory);
        $challenge = $scheme;
        $this->reject = static function () use ($buildErrorResponse, $challenge): ResponseInterface {
            $response = $buildErrorResponse(401, [], ['message' => 'Authentication required.']);

            return $challenge === '' ? $response : $response->withHeader('WWW-Authenticate', $challenge);
        };
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($this->isAnonymous)($request)) {
            return $handler->handle(($this->applySession)($request, null));
        }

        $token = ($this->readToken)($request);
        $result = $token === null ? null : ($this->authenticate)($token);

        if ($result === null) {
            return ($this->reject)();
        }

        return $handler->handle(($this->applySession)($request, $result));
    }
}
