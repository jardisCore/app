<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation;

/**
 * Thrown by generated Validate classes (Command-level field
 * validation, Repository-level entity validation) when incoming data
 * fails structural checks. The generated CommandHandler dispatch
 * catches this BEFORE the generic \Throwable branch and maps it to a
 * 400 response — a field-validation failure is a client input error
 * (400), not an unexpected internal fault (500)
 * (docs/rules-layer/PRD.md A5, PLAN.md Vertrag 4).
 */
final class ValidationException extends \RuntimeException
{
}
