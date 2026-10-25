<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\System;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds the in-process request of one stage of a system run: method, path (a query string is carried by the URI),
 * a JSON body when there is one, and `Authorization: Bearer <token>` when a token is given. Pure; no I/O.
 */
final class BuildSystemRequest
{
    public function __construct(
        private readonly ServerRequestFactoryInterface $serverRequestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function __invoke(string $method, string $path, string $body, ?string $token): ServerRequestInterface
    {
        $request = $this->serverRequestFactory->createServerRequest($method, $path);

        if ($body !== '') {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($body));
        }

        return $token === null ? $request : $request->withHeader('Authorization', 'Bearer ' . $token);
    }
}
