<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Response;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Emits a PSR-7 `ResponseInterface` to the SAPI (E8/E13 - the fourth error
 * timepoint from the PRD's boundary-error vertrag, F9): status line,
 * headers, then body - unless the original request was HEAD, in which case
 * RFC 7231 §4.3.2 forbids a body; `Content-Length` (derived from
 * `getBody()->getSize()`, whenever that size is known - HEAD or not) is
 * still sent.
 *
 * `headers_sent()` guards only the status-line/header phase: if headers
 * were already sent by something upstream, sending them again would raise
 * a PHP warning, so that phase is skipped entirely - the body is still
 * written best-effort regardless (there is no way to "unsend" headers, but
 * there is nothing wrong with still trying to deliver a body).
 *
 * Testability (documented decision): PHP's CLI SAPI turns `header()` into a
 * silent no-op with nothing observable via `headers_list()`, so the actual
 * header-emitting call and the `headers_sent()` query are both injectable
 * (`$sendHeader`/`$isHeadersSent`, defaulting to the real PHP functions) -
 * tests substitute recording fakes for both. Body output always goes
 * through the real `echo`, which IS observable in-process via output
 * buffering. Real, over-the-wire header transmission is proven only by the
 * SAPI smoke test (P6 AK).
 *
 * Any Throwable escaping the emit itself is caught here and never
 * re-thrown: a best-effort, detail-free 500 status line is attempted
 * (only if headers were not already sent) and the error goes to
 * `error_log` - a broken emit must never crash the process worse than a
 * silent failure would.
 */
final class EmitResponse
{
    private const CHUNK_SIZE = 8192;

    private readonly Closure $sendHeader;
    private readonly Closure $isHeadersSent;

    /**
     * @param Closure(string, bool): void|null $sendHeader defaults to PHP's `header()`
     * @param Closure(): bool|null $isHeadersSent defaults to PHP's `headers_sent()`
     */
    public function __construct(?Closure $sendHeader = null, ?Closure $isHeadersSent = null)
    {
        $this->sendHeader = $sendHeader ?? static function (string $line, bool $replace): void {
            header($line, $replace);
        };
        $this->isHeadersSent = $isHeadersSent ?? static fn (): bool => headers_sent();
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): void
    {
        try {
            $this->emit($request, $response);
        } catch (Throwable $exception) {
            $this->emitFallback($exception);
        }
    }

    private function emit(ServerRequestInterface $request, ResponseInterface $response): void
    {
        if (!($this->isHeadersSent)()) {
            $response = $this->withContentLength($response);
            $this->sendStatusLine($response);
            $this->sendHeaders($response);
        }

        if (strtoupper($request->getMethod()) === 'HEAD') {
            return;
        }

        $this->sendBody($response);
    }

    private function withContentLength(ResponseInterface $response): ResponseInterface
    {
        $size = $response->getBody()->getSize();

        if ($size === null || $response->hasHeader('Content-Length')) {
            return $response;
        }

        return $response->withHeader('Content-Length', (string) $size);
    }

    private function sendStatusLine(ResponseInterface $response): void
    {
        $line = trim(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase(),
        ));

        ($this->sendHeader)($line, true);
    }

    private function sendHeaders(ResponseInterface $response): void
    {
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $index => $value) {
                ($this->sendHeader)(sprintf('%s: %s', $name, $value), $index === 0);
            }
        }
    }

    private function sendBody(ResponseInterface $response): void
    {
        $body = $response->getBody();
        $body->rewind();

        while (!$body->eof()) {
            echo $body->read(self::CHUNK_SIZE);
        }
    }

    private function emitFallback(Throwable $exception): void
    {
        error_log(sprintf(
            'EmitResponse failed: %s: %s in %s:%d',
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
        ));

        if (!($this->isHeadersSent)()) {
            ($this->sendHeader)('HTTP/1.1 500 Internal Server Error', true);
        }
    }
}
