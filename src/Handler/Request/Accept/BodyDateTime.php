<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request\Accept;

use DateTimeImmutable;
use DateTimeZone;
use JardisSupport\Validation\Validator\DateTime;

/**
 * Accepts a JSON body value as an RFC 3339 instant (offset mandatory, `Z` and fractions allowed) and
 * normalises it to UTC right here - the one place a wire string becomes an instant, so no offset is
 * silently dropped on the way to the store.
 *
 * The form is judged by the one parser of `jardissupport/validation` ({@see DateTime}).
 * Atomic closure - one entry point.
 */
final class BodyDateTime
{
    public function __invoke(mixed $value): ?DateTimeImmutable
    {
        return is_string($value) && (new DateTime())->validateValue($value, DateTime::rfc3339()) === null
            ? (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))
            : null;
    }
}
