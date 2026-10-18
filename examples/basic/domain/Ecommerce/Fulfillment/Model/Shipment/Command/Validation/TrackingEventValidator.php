<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Contain;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\NotEmpty;

/**
 * Request validator for TrackingEvent command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL string (no value list, no FK) → NotEmpty (a blank string is empty, R78), other NOT NULL → NotBlank
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
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(50));
        $this->validator->field('status')
            ->validates(NotBlank::class)
            ->validates(
                Contain::class,
                Contain::oneOf(
                    [
                        'pending',
                        'confirmed',
                        'picking',
                        'packed',
                        'picked_up',
                        'shipped',
                        'in_transit',
                        'delivered',
                        'failed',
                    ]
                )
            );
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
