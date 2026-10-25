<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\System;

use Closure;
use Psr\Http\Message\ResponseInterface;

/**
 * Reads the session token out of the answer of a session door (sign in, switch tenant): the field `data.access_token`
 * (RFC 6749 section 5.1, the access token response; the doors answer `access_token`, `token_type`, `expires_in`,
 * `refresh_token`).
 *
 * The value must be a non-empty string AND be accepted by the authenticate closure
 * ({@see \JardisCore\App\Handler\Session\AuthenticateToken}, the same check `RequireSession` runs). Fail-closed:
 * an answer that is not a JSON object with an object `data`, a missing / non-string / empty field, or a value the
 * authenticator does not accept -> null. Other fields of `data` (e.g. `refresh_token`) are never looked at.
 */
final class ReadAccessToken
{
    /** Name of the token field in `data` (RFC 6749 section 5.1). */
    public const FIELD = 'access_token';

    /**
     * @param Closure(string): ?\JardisSupport\Contract\Auth\AuthResultInterface $authenticate
     */
    public function __construct(private readonly Closure $authenticate)
    {
    }

    public function __invoke(ResponseInterface $response): ?string
    {
        $decoded = json_decode((string) $response->getBody(), true);
        $data = is_array($decoded) ? ($decoded['data'] ?? null) : null;
        if (!is_array($data)) {
            return null;
        }

        $token = $data[self::FIELD] ?? null;
        if (!is_string($token) || $token === '') {
            return null;
        }

        return ($this->authenticate)($token) !== null ? $token : null;
    }
}
