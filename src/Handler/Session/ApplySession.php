<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Session;

use JardisSupport\Contract\Auth\AuthResultInterface;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\RoleAwareAuthResultInterface;
use JardisSupport\Contract\Auth\SessionAwareAuthResultInterface;
use JardisSupport\Contract\Auth\TenantAwareAuthResultInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Binds a verified session to the request - the only writer of the attributes
 * `RequestAttribute::SUBJECT`, `TENANT`, `ROLE`, `SUBJECT_ID` and `SESSION`.
 *
 * Whatever the attributes held before (a path placeholder named like them, an
 * upstream middleware) is overwritten or removed, so a client can never inject a
 * subject or tenant. With a result: SUBJECT is set; TENANT is set when the result
 * is tenant-aware and carries a non-empty tenant, otherwise the TENANT attribute is
 * absent (never null, never ''). Without a result (anonymous request) both
 * attributes are removed.
 *
 * ROLE (the role claim of the session) is set only when the result is a
 * `RoleAwareAuthResultInterface` and `getRole()` is non-empty; otherwise the attribute is
 * absent (never null, never ''). It is a plain attribute, not a guard - core/app authorizes nothing.
 *
 * SUBJECT_ID (bare subject identifier) and SESSION (reference that revokes exactly this
 * session) are set only when the result is a `SessionAwareAuthResultInterface` and the
 * respective value is non-empty; otherwise the attribute is absent (never null, never '').
 * Like SUBJECT/TENANT they are always removed first, so a client can never inject them.
 */
final class ApplySession
{
    public function __invoke(ServerRequestInterface $request, ?AuthResultInterface $result): ServerRequestInterface
    {
        $request = $request
            ->withoutAttribute(RequestAttribute::SUBJECT)
            ->withoutAttribute(RequestAttribute::TENANT)
            ->withoutAttribute(RequestAttribute::ROLE)
            ->withoutAttribute(RequestAttribute::SUBJECT_ID)
            ->withoutAttribute(RequestAttribute::SESSION);

        if ($result === null) {
            return $request;
        }

        $request = $request->withAttribute(RequestAttribute::SUBJECT, $result->getSubject());

        $tenant = $result instanceof TenantAwareAuthResultInterface ? $result->getTenant() : null;

        if ($tenant !== null && $tenant !== '') {
            $request = $request->withAttribute(RequestAttribute::TENANT, $tenant);
        }

        $role = $result instanceof RoleAwareAuthResultInterface ? $result->getRole() : null;

        if ($role !== null && $role !== '') {
            $request = $request->withAttribute(RequestAttribute::ROLE, $role);
        }

        if (!$result instanceof SessionAwareAuthResultInterface) {
            return $request;
        }

        $subjectId = $result->getSubjectId();
        if ($subjectId !== null && $subjectId !== '') {
            $request = $request->withAttribute(RequestAttribute::SUBJECT_ID, $subjectId);
        }

        $session = $result->getSessionReference();
        if ($session !== null && $session !== '') {
            $request = $request->withAttribute(RequestAttribute::SESSION, $session);
        }

        return $request;
    }
}
