<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Request validator for TrackingEvent command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class TrackingEventValidator
{
    private ?CompositeFieldValidator $validator = null;

    /**
     * Build validation rules for command DTO (lazy-initialized).
     *
     * @return CompositeFieldValidator
     */
    public function __invoke(): CompositeFieldValidator
    {
        if ($this->validator !== null) {
            return $this->validator;
        }

        $this->validator = new CompositeFieldValidator();

        $this->validator->field('eventCode')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(50));
        $this->validator->field('status')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(100));
        $this->validator->field('location')
            ->validates(Length::class, Length::max(255));
        $this->validator->field('postalCode')
            ->validates(Length::class, Length::max(20));
        $this->validator->field('occurredAt')
            ->validates(NotBlank::class)
            ->validates(DateTime::class);
        $this->validator->field('reportedAt')
            ->validates(NotBlank::class)
            ->validates(DateTime::class);

        return $this->validator;
    }
}
