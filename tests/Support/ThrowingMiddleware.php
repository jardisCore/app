<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Test fake: a PSR-15 middleware that always throws - used to prove
 * HandleThrowable catches exceptions escaping from inside the middleware
 * pipeline, not only from the final route handler (E8, outermost-layer
 * proof).
 */
final class ThrowingMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $message = 'inner middleware exploded')
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        throw new RuntimeException($this->message);
    }
}
