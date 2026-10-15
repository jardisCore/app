<?php

declare(strict_types=1);

namespace JardisCore\App\Middleware;

use Closure;
use InvalidArgumentException;
use JardisCore\App\Handler\Response\BuildErrorResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Answers 403 when a request attribute a door depends on is missing.
 *
 * Register it per route, behind the middleware that sets the attribute (e.g. {@see RequireSession}
 * for the session attributes). For each configured name, in the given order, the attribute must be
 * present as a non-empty string; the first one that is not ends the request: the handler is never
 * called and the canonical envelope answers 403 with `errors.messageKey = request.attributeMissing`
 * and `errors.context.attribute = <name>`. Otherwise the request passes unchanged.
 *
 * It only checks presence. It authorizes nothing and does not implement
 * {@see \JardisCore\App\Contract\SessionGuardInterface}, so it never counts as the session guard
 * of `AppConfig::$requireSessionGuard`.
 */
final class RequireRequestAttribute implements MiddlewareInterface
{
    public const MESSAGE_KEY = 'request.attributeMissing';

    /** @var list<string> */
    private readonly array $names;
    private readonly Closure $reject;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        string ...$names,
    ) {
        if ($names === []) {
            throw new InvalidArgumentException('RequireRequestAttribute needs at least one attribute name.');
        }
        foreach ($names as $name) {
            if ($name === '') {
                throw new InvalidArgumentException('RequireRequestAttribute attribute names must not be empty.');
            }
        }
        $this->names = array_values($names);

        $buildErrorResponse = new BuildErrorResponse($responseFactory, $streamFactory);
        $this->reject = static fn (string $name): ResponseInterface => $buildErrorResponse(
            403,
            [],
            [
                'message' => sprintf('Required request attribute "%s" is missing.', $name),
                'messageKey' => self::MESSAGE_KEY,
                'context' => ['attribute' => $name],
            ],
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->names as $name) {
            $value = $request->getAttribute($name);
            if (!is_string($value) || $value === '') {
                return ($this->reject)($name);
            }
        }

        return $handler->handle($request);
    }
}
