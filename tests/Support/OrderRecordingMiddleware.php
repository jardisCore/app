<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test fake: a PSR-15 middleware that records its label into a shared
 * CallLog both before delegating to the next handler (request direction)
 * and after receiving its response (response direction) - used to prove
 * the E9 pipeline ordering deterministically in both directions.
 */
final class OrderRecordingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CallLog $log,
        private readonly string $label,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->log->record($this->label . ':request');
        $response = $handler->handle($request);
        $this->log->record($this->label . ':response');

        return $response;
    }
}
