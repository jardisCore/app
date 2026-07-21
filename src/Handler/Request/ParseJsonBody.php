<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use JardisCore\App\Exception\InvalidJsonBody;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The one JSON body parser (E7/F8b). Works lazily on the request's existing
 * body stream — rewinds before reading and rewinds again afterwards, so the
 * stream itself is never replaced or left exhausted. Callers can therefore
 * call `$request->getBody()` again afterwards and get the byte-identical
 * raw payload back (webhook-HMAC case), and calling this parser twice on
 * the same request is idempotent.
 */
final class ParseJsonBody
{
    /**
     * @return array<array-key, mixed>
     */
    public function __invoke(ServerRequestInterface $request): array
    {
        $body = $request->getBody();
        $body->rewind();
        $raw = $body->getContents();
        $body->rewind();

        if ($raw === '') {
            throw new InvalidJsonBody('Request body is empty; a JSON body is required.');
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidJsonBody('Request body is not valid JSON.', previous: $exception);
        }

        if (!is_array($decoded)) {
            throw new InvalidJsonBody('Decoded JSON body must be an object or array.');
        }

        return $decoded;
    }
}
