<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Middleware;

use Closure;
use JardisCore\App\Middleware\RequireSession;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\TokenAuthenticator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;

/**
 * Builds the standard session middleware: {@see RequireSession} over a
 * `TokenAuthenticator` backed by the resolved token store.
 */
final class BuildSessionMiddleware
{
    /**
     * @param Closure(): \JardisSupport\Contract\Auth\TokenStoreInterface $resolveTokenStore
     */
    public function __construct(
        private readonly Closure $resolveTokenStore,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function __invoke(): MiddlewareInterface
    {
        if (!class_exists(TokenAuthenticator::class)) {
            throw new RuntimeException(
                'The "session" middleware requires jardissupport/auth: composer require jardissupport/auth'
            );
        }

        return new RequireSession(
            new TokenAuthenticator(($this->resolveTokenStore)()),
            Credential::token(...),
            $this->responseFactory,
            $this->streamFactory,
        );
    }
}
