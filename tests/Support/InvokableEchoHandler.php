<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test fake: an invokable object (implements only __invoke, not
 * RequestHandlerInterface) - used to prove Routes converts any PHP callable
 * shape, including invokable objects, to a Closure immediately (E4).
 */
final class InvokableEchoHandler
{
    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}
