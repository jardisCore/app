<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Session;

use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\SubjectGroupsInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Binds the groups of the subject to the request - the only writer of the attributes
 * `RequestAttribute::GROUPS` and `RequestAttribute::PERSONAL_GROUP`.
 *
 * Whatever the attributes held before (a path placeholder named like them, an upstream middleware) is removed
 * first, so a client can never inject groups. With a result: GROUPS (`list<int>`) is set only when the subject
 * has at least one group, PERSONAL_GROUP (`int`) only when it has a personal group; otherwise the attribute is
 * absent (never `[]`, never null). Without a result (anonymous request) both attributes stay removed.
 *
 * A plain attribute, not a guard - core/app authorizes nothing; the readers for a route are
 * {@see \JardisCore\App\Handler\Request\ReadRequestAttributeIntList} and
 * {@see \JardisCore\App\Handler\Request\ReadRequestAttribute}.
 */
final class ApplyGroups
{
    public function __invoke(ServerRequestInterface $request, ?SubjectGroupsInterface $groups): ServerRequestInterface
    {
        $request = $request
            ->withoutAttribute(RequestAttribute::GROUPS)
            ->withoutAttribute(RequestAttribute::PERSONAL_GROUP);

        if ($groups === null) {
            return $request;
        }

        $ids = $groups->getGroups();

        if ($ids !== []) {
            $request = $request->withAttribute(RequestAttribute::GROUPS, $ids);
        }

        $personal = $groups->getPersonalGroup();

        if ($personal !== null) {
            $request = $request->withAttribute(RequestAttribute::PERSONAL_GROUP, $personal);
        }

        return $request;
    }
}
