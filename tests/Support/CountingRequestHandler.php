<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test fake: a PSR-15 RequestHandlerInterface that counts how often it was reached and answers 200.
 */
final class CountingRequestHandler implements RequestHandlerInterface
{
    public int $calls = 0;

    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->calls++;

        return $this->responseFactory->createResponse(200);
    }
}
