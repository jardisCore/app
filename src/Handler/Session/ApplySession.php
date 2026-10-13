<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Session;

use JardisSupport\Contract\Auth\AuthResultInterface;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\TenantAwareAuthResultInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Binds a verified session to the request - the only writer of the attributes
 * `RequestAttribute::SUBJECT` and `RequestAttribute::TENANT`.
 *
 * Whatever the attributes held before (a path placeholder named like them, an
 * upstream middleware) is overwritten or removed, so a client can never inject a
 * subject or tenant. With a result: SUBJECT is set; TENANT is set when the result
 * is tenant-aware and carries a non-empty tenant, otherwise the TENANT attribute is
 * absent (never null, never ''). Without a result (anonymous request) both
 * attributes are removed.
 */
final class ApplySession
{
    public function __invoke(ServerRequestInterface $request, ?AuthResultInterface $result): ServerRequestInterface
    {
        $request = $request
            ->withoutAttribute(RequestAttribute::SUBJECT)
            ->withoutAttribute(RequestAttribute::TENANT);

        if ($result === null) {
            return $request;
        }

        $request = $request->withAttribute(RequestAttribute::SUBJECT, $result->getSubject());

        $tenant = $result instanceof TenantAwareAuthResultInterface ? $result->getTenant() : null;

        return ($tenant === null || $tenant === '')
            ? $request
            : $request->withAttribute(RequestAttribute::TENANT, $tenant);
    }
}
