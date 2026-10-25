<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\System;

use Closure;
use Psr\Http\Message\ResponseInterface;

/**
 * Reads the session token out of the answer of a session door (sign in, switch tenant).
 *
 * This is a PROBE over the answer structure, a deliberate compromise (the route table names no output field as the
 * token): all string values of the envelope's `data` (recursively) are offered to the authenticate closure
 * ({@see \JardisCore\App\Handler\Session\AuthenticateToken}, the same check `RequireSession` runs); the token is the
 * ONE distinct string it accepts. Fail-closed: no accepted string, or more than one distinct accepted string,
 * or an answer that is not a JSON object with an object `data` -> null. The same string found twice counts once.
 */
final class ReadSessionToken
{
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

        $accepted = [];
        foreach (array_unique($this->strings($data)) as $candidate) {
            if (($this->authenticate)($candidate) !== null) {
                $accepted[] = $candidate;
            }
        }

        return count($accepted) === 1 ? $accepted[0] : null;
    }

    /**
     * @param array<mixed> $value
     * @return list<string>
     */
    private function strings(array $value): array
    {
        $strings = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                array_push($strings, ...$this->strings($item));
            } elseif (is_string($item) && $item !== '') {
                $strings[] = $item;
            }
        }

        return $strings;
    }
}
