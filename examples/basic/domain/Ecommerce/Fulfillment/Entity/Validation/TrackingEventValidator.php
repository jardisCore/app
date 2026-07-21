<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Entity\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Validator for TrackingEvent entity.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - UUID type → Uuid
 * - JSON type → Json
 * - DateTime types → DateTime
 * - Convention-based: email field → Email, url field → Url, etc.
 */
class TrackingEventValidator
{
    private ?CompositeFieldValidator $validator = null;

    /**
     * Build validation rules for entity (lazy-initialized).
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
