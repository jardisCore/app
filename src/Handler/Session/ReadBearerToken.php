<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Session;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads the access token of a request from exactly one header line.
 *
 * Default form: `Authorization: Bearer <token>` (scheme compared case-insensitively
 * as RFC 7235 demands, token compared as sent). With an empty `$scheme` the whole
 * header value is the token (e.g. `X-Api-Token: <token>`).
 *
 * Returns null whenever there is no usable token: header absent, repeated header,
 * wrong scheme, empty token or a token containing whitespace. The caller treats
 * every null the same way - the reason is never told to the client.
 */
final class ReadBearerToken
{
    public function __construct(
        private readonly string $headerName = 'Authorization',
        private readonly string $scheme = 'Bearer',
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ?string
    {
        $lines = $request->getHeader($this->headerName);
        if (count($lines) !== 1) {
            return null;
        }

        $value = trim($lines[0]);
        if ($this->scheme !== '') {
            $prefix = $this->scheme . ' ';
            if (strncasecmp($value, $prefix, strlen($prefix)) !== 0) {
                return null;
            }
            $value = trim(substr($value, strlen($prefix)));
        }

        if ($value === '' || preg_match('/\s/', $value) === 1) {
            return null;
        }

        return $value;
    }
}
