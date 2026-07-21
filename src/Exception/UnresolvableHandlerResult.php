<?php

declare(strict_types=1);

namespace JardisCore\App\Exception;

/**
 * Thrown by `ResolveResponse` (E6) when a route/middleware handler returns
 * something that is neither a `DomainResponseInterface` nor a PSR-7
 * `ResponseInterface` - the only two return shapes the App-Layer knows how
 * to turn into an HTTP response. Deliberately a plain `AppException`
 * subtype (not a dedicated 4xx/5xx mapping of its own): it is a
 * programming error in application-supplied handler code, not a client
 * input problem, so it escapes the same way any other unexpected
 * `Throwable` does - caught by the outermost `HandleThrowable` (E8) and
 * mapped to the generic 500 boundary response.
 */
final class UnresolvableHandlerResult extends AppException
{
}
