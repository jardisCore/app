<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Error;

use Closure;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Exception\InvalidJsonBody;
use JardisCore\App\Handler\Response\BuildErrorResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Outermost pipeline layer (E8/F9): wraps the rest of request processing -
 * received as a Closure `fn(ServerRequestInterface): ResponseInterface` -
 * in a try/catch boundary no exception can escape (Decorator: the inner
 * processing, pipeline included, is just the wrapped Closure).
 *
 * - `InvalidJsonBody` -> canonical 400 envelope (F8c), the exception's own
 *   (non-sensitive, client-facing) message is included.
 * - Any other Throwable -> 500 with a GENERIC body (no message, class
 *   name, or trace) unless `AppConfig::$debug` is true; the full error
 *   always goes to the injected PSR-3 logger at `error` level. A null
 *   logger, or a logger that itself throws, falls back to `error_log`
 *   (see LogThrowable) - the 500 response is unaffected either way.
 */
final class HandleThrowable
{
    private readonly Closure $buildEnvelope;
    private readonly Closure $logThrowable;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ?LoggerInterface $logger,
        private readonly AppConfig $config,
    ) {
        $this->buildEnvelope = (new BuildErrorResponse($responseFactory, $streamFactory))->__invoke(...);
        $this->logThrowable = (new LogThrowable($logger))->__invoke(...);
    }

    public function __invoke(Closure $inner, ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $inner($request);
        } catch (InvalidJsonBody $exception) {
            return ($this->buildEnvelope)(400, errors: ['message' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            return $this->handleUnexpectedThrowable($exception);
        }
    }

    private function handleUnexpectedThrowable(Throwable $exception): ResponseInterface
    {
        ($this->logThrowable)($exception);

        if (!$this->config->debug) {
            return ($this->buildEnvelope)(500);
        }

        return ($this->buildEnvelope)(500, errors: [
            'message' => $exception->getMessage(),
            'exception' => $exception::class,
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
