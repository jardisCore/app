<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use Closure;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\SeeAllRight;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Answers whether the session of the request "sees all" for one right: true when `RequestAttribute::RIGHTS`
 * holds the superuser `*` (the owner) or the "sees all" entry of exactly this right
 * (`SeeAllRight::RESOURCE . ':' . $right`, e.g. `seeAll:sales.order:read`). The route then passes
 * `groups = null` to the access filter.
 *
 * False for the entry of another right, for a RIGHTS attribute that is absent and for one that is not a list of
 * strings (unusable). Fail-closed: the closure only widens on an exact match. It only reads, it never decides
 * on access and never throws (but `\Error`); the entry form is the contract constant, not a right check.
 */
final class ReadSeesAll
{
    private readonly Closure $readRights;

    public function __construct()
    {
        $this->readRights = (new ReadRequestAttributeList())->__invoke(...);
    }

    public function __invoke(ServerRequestInterface $request, string $right): bool
    {
        $rights = ($this->readRights)($request, RequestAttribute::RIGHTS);

        return $rights !== null
            && (in_array('*', $rights, true) || in_array(SeeAllRight::RESOURCE . ':' . $right, $rights, true));
    }
}
