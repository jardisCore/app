<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request\Accept;

use JardisSupport\Validation\Validator\DateTime;

/**
 * Accepts a path or query value as a calendar-free H:i:s.
 *
 * The form is judged by the one parser of `jardissupport/validation` ({@see DateTime}); a transport value
 * is a string end to end, so the accepted string is handed on unchanged. Atomic closure - one entry point.
 */
final class TransportTime
{
    public function __invoke(mixed $value): ?string
    {
        return is_string($value) && (new DateTime())->validateValue($value, ['format' => 'H:i:s']) === null
            ? $value
            : null;
    }
}
