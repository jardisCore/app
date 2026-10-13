<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Session;

use Closure;
use JardisSupport\Contract\Auth\AuthenticatorInterface;
use JardisSupport\Contract\Auth\AuthResultInterface;

/**
 * Asks the injected authenticator port whether a token belongs to a valid session.
 *
 * Returns the successful result, or null for every kind of failure (unknown, broken,
 * expired, revoked token; a "successful" result without a subject counts as failure).
 * The failure reason of the port is deliberately dropped here. A Throwable of the
 * authenticator (e.g. token store unreachable) is NOT swallowed: it is a technical
 * error and ends in the generic 500 boundary, never in a 401.
 */
final class AuthenticateToken
{
    /**
     * @param Closure(string): \JardisSupport\Contract\Auth\CredentialInterface $credentialFor
     *        builds the token credential (core/app does not depend on support/auth;
     *        pass e.g. `\JardisSupport\Auth\Data\Credential::token(...)`)
     */
    public function __construct(
        private readonly AuthenticatorInterface $authenticator,
        private readonly Closure $credentialFor,
    ) {
    }

    public function __invoke(string $token): ?AuthResultInterface
    {
        $result = $this->authenticator->authenticate(($this->credentialFor)($token));

        if (!$result->isSuccess()) {
            return null;
        }

        $subject = $result->getSubject();

        return ($subject === null || $subject === '') ? null : $result;
    }
}
