<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request\Accept;

use DateTimeImmutable;
use JardisSupport\Validation\Validator\DateTime;

/**
 * Accepts a JSON body value as a calendar-valid `Y-m-d` and hands it on as the instant.
 *
 * The form is judged by the one parser of `jardissupport/validation` ({@see DateTime}).
 * Atomic closure - one entry point.
 */
final class BodyDate
{
    public function __invoke(mixed $value): ?DateTimeImmutable
    {
        return is_string($value) && (new DateTime())->validateValue($value, DateTime::dateOnly()) === null
            ? new DateTimeImmutable($value)
            : null;
    }
}
