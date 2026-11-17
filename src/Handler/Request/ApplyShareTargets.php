<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use JardisSupport\Contract\Auth\RequestAttribute;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Binds the resolved share targets to the request - the only writer of `RequestAttribute::SHARE_TARGETS`.
 *
 * Whatever the attribute held before (a path placeholder named like it, an upstream middleware) is removed
 * first, so a client can never inject targets. Then: `null` (no indication) -> the attribute stays absent (never
 * `[]`, never null); `false` (not resolvable) or the pairs group => level word -> set as given.
 */
final class ApplyShareTargets
{
    /**
     * @param array<int, string>|false|null $targets
     */
    public function __invoke(ServerRequestInterface $request, array|false|null $targets): ServerRequestInterface
    {
        $request = $request->withoutAttribute(RequestAttribute::SHARE_TARGETS);

        return $targets === null ? $request : $request->withAttribute(RequestAttribute::SHARE_TARGETS, $targets);
    }
}
