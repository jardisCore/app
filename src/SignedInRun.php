<?php

declare(strict_types=1);

namespace JardisCore\App;

use Closure;
use JardisCore\App\Data\SessionDoor;
use JardisCore\App\Exception\SystemRunRefused;
use JardisCore\App\Handler\Session\AuthenticateToken;
use JardisCore\App\Handler\System\BuildSwitchBody;
use JardisCore\App\Handler\System\BuildSystemRequest;
use JardisCore\App\Handler\System\ReadSessionToken;
use JardisSupport\Contract\Auth\AuthenticatorInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * A system run (CLI, job) gets its rights like any user: sign in through the sign-in door, switch to the tenant
 * through the switch-tenant door, then call the target door with `Authorization: Bearer` - every stage through
 * {@see App::handle()}, so the target runs through `RequireSession` and `RequireRight` exactly like a browser request.
 * No special value, no second check.
 *
 * Orchestrator: builds the requests, hands them to the app, reads the token, routes on the status - the work lives in
 * closures under `Handler/System/` (request, switch body, token). The one public entry point is `handle()`.
 *
 * Refusals ({@see SystemRunRefused}): empty tenant (before any request), a session door that did not answer 2xx,
 * no single token in the answer. The answer of the target door is returned unchanged, also 4xx. The session is not
 * signed out afterwards; it lives until it expires.
 *
 * The token is found by a probe over the answer structure, see {@see ReadSessionToken}.
 */
final class SignedInRun
{
    private readonly Closure $buildRequest;
    private readonly Closure $buildSwitchBody;
    private readonly Closure $readToken;

    /**
     * @param Closure(string): \JardisSupport\Contract\Auth\CredentialInterface $credentialFor
     *        token string to credential, as for `RequireSession`,
     *        e.g. `\JardisSupport\Auth\Data\Credential::token(...)`
     * @param string $tenantField the one body property of the switch-tenant door that carries the tenant
     */
    public function __construct(
        private readonly App $app,
        AuthenticatorInterface $authenticator,
        Closure $credentialFor,
        private readonly SessionDoor $signIn,
        private readonly SessionDoor $switchTenant,
        private readonly string $tenantField,
        ?ServerRequestFactoryInterface $serverRequestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $psr17 = new Psr17Factory();
        $this->buildRequest = (new BuildSystemRequest(
            $serverRequestFactory ?? $psr17,
            $streamFactory ?? $psr17,
        ))->__invoke(...);
        $this->buildSwitchBody = (new BuildSwitchBody())->__invoke(...);
        $this->readToken = (new ReadSessionToken(
            (new AuthenticateToken($authenticator, $credentialFor))->__invoke(...),
        ))->__invoke(...);
    }

    /**
     * @param string $signInBody JSON body of the sign-in door (the credentials of the system user)
     */
    public function handle(
        string $tenant,
        string $signInBody,
        string $method,
        string $path,
        string $body = '',
    ): ResponseInterface {
        if ($tenant === '') {
            throw new SystemRunRefused('no tenant given');
        }

        $signedIn = $this->session($this->signIn, $signInBody, null, 'sign-in');
        $switched = $this->session(
            $this->switchTenant,
            ($this->buildSwitchBody)($this->tenantField, $tenant),
            $signedIn,
            'tenant switch',
        );

        return $this->app->handle(($this->buildRequest)($method, $path, $body, $switched));
    }

    /**
     * One session stage: request through the app, 2xx or refusal, the token of the answer or refusal.
     */
    private function session(SessionDoor $door, string $body, ?string $token, string $stage): string
    {
        $response = $this->app->handle(($this->buildRequest)($door->method, $door->path, $body, $token));
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new SystemRunRefused($stage . ' door did not answer 2xx', $status);
        }

        return ($this->readToken)($response)
            ?? throw new SystemRunRefused('no single session token in the answer of the ' . $stage . ' door', $status);
    }
}
