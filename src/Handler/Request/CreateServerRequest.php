<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Builds a PSR-7 ServerRequest from the SAPI globals (or injected
 * equivalents). PSR-17 factories are received purely as interfaces
 * (E12) — no concrete factory is ever instantiated here.
 *
 * Raw-Body-Invariante (PRD §5): the body is read from `php://input`
 * (or an injected substitute) exactly once, right here, into a seekable
 * stream. Nothing downstream may replace or re-read the SAPI source —
 * every later consumer (ParseJsonBody, handlers, …) only ever operates
 * on the resulting ServerRequestInterface's body stream.
 */
final class CreateServerRequest
{
    private readonly ServerRequestCreator $creator;

    public function __construct(
        ServerRequestFactoryInterface $serverRequestFactory,
        UriFactoryInterface $uriFactory,
        UploadedFileFactoryInterface $uploadedFileFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        $this->creator = new ServerRequestCreator(
            $serverRequestFactory,
            $uriFactory,
            $uploadedFileFactory,
            $streamFactory,
        );
    }

    /**
     * @param array<string, mixed>|null $server  defaults to $_SERVER
     * @param array<string, string>|null $headers defaults to getallheaders()/derived from $server
     * @param array<string, mixed>|null $cookies defaults to $_COOKIE
     * @param array<string, mixed>|null $query defaults to $_GET
     * @param array<string, mixed>|null $post parsed body (form-encoded); null = not applicable
     * @param array<string, mixed>|null $files defaults to $_FILES
     * @param resource|string|StreamInterface|null $body input source; defaults to a fresh
     *        `php://input` handle. Inject a string/stream in tests — never replace this
     *        value after construction, that would violate the Raw-Body-Invariante.
     */
    public function __invoke(
        ?array $server = null,
        ?array $headers = null,
        ?array $cookies = null,
        ?array $query = null,
        ?array $post = null,
        ?array $files = null,
        mixed $body = null,
    ): ServerRequestInterface {
        $server ??= $_SERVER;
        $headers ??= $this->resolveHeaders($server);
        $cookies ??= $_COOKIE;
        $query ??= $_GET;
        $files ??= $_FILES;
        $body ??= fopen('php://input', 'rb') ?: '';

        return $this->creator->fromArrays($server, $headers, $cookies, $query, $post, $files, $body);
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private function resolveHeaders(array $server): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        return ServerRequestCreator::getHeadersFromServer($server);
    }
}
