<?php

declare(strict_types=1);

namespace ExampleApp\Support;

use JardisSupport\Contract\Auth\RequestAttribute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stand-in for the session door of this example: it has no session layer, so every request runs as one
 * fixed tenant and one fixed subject, placed under the request attributes the generated doors read
 * (`RequestAttribute::TENANT`, `RequestAttribute::SUBJECT_ID`). A real application puts
 * {@see \JardisCore\App\Middleware\RequireSession} here, which fills the same attributes from a token.
 */
final class SingleTenantSession implements MiddlewareInterface
{
    public function __construct(
        private readonly string $tenant = 'example-tenant',
        private readonly string $subject = 'example-app',
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle(
            $request
                ->withAttribute(RequestAttribute::TENANT, $this->tenant)
                ->withAttribute(RequestAttribute::SUBJECT_ID, $this->subject)
        );
    }
}
