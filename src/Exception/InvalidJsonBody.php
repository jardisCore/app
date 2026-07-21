<?php

declare(strict_types=1);

namespace JardisCore\App\Exception;

/**
 * Thrown when a request body cannot be decoded as a JSON structure — either
 * because it is syntactically invalid or because it is empty (F8: the
 * canonical 400 case for an incoming request; the HTTP mapping itself is
 * decided by the error pipeline, not here).
 */
final class InvalidJsonBody extends AppException
{
}
