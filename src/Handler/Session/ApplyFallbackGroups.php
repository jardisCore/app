<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Session;

use JardisSupport\Contract\Auth\RequestAttribute;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Binds the fallback groups to the request - the only writer of the attribute
 * `RequestAttribute::FALLBACK_GROUPS`.
 *
 * Whatever the attribute held before (a path placeholder named like it, an upstream middleware) is removed
 * first, so a client can never inject a fallback group. Of the answers (`information kind => ?int`) the `null`
 * ones fall away; the attribute (`array<int, int>`) is set only when at least one answer is left, otherwise it
 * stays absent (never `[]`, never null).
 *
 * A plain attribute, not a guard - core/app authorizes nothing.
 */
final class ApplyFallbackGroups
{
    /**
     * @param array<int, int|null> $answers information kind => fallback group, `null` = the kind has none
     */
    public function __invoke(ServerRequestInterface $request, array $answers): ServerRequestInterface
    {
        $request = $request->withoutAttribute(RequestAttribute::FALLBACK_GROUPS);

        $groups = array_filter($answers, static fn (?int $group): bool => $group !== null);

        if ($groups === []) {
            return $request;
        }

        return $request->withAttribute(RequestAttribute::FALLBACK_GROUPS, $groups);
    }
}
