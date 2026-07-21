<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test fake: a PSR-15 RequestHandlerInterface that records a fixed label
 * into a shared CallLog and returns a fixed response - used as the
 * innermost handler in pipeline-ordering tests (E9).
 */
final class RecordingRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly CallLog $log,
        private readonly string $label,
        private readonly ResponseInterface $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->log->record($this->label);

        return $this->response;
    }
}
