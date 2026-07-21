<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test fake: a PSR-15 RequestHandlerInterface that always returns the same
 * injected response, regardless of the request it receives.
 */
final class FixedResponseHandler implements RequestHandlerInterface
{
    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}
