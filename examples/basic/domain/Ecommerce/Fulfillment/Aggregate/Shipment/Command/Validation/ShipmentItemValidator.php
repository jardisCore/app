<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\Positive;

/**
 * Request validator for ShipmentItem command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class ShipmentItemValidator
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

        $this->validator->field('productSku')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(100));
        $this->validator->field('productName')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(255));
        $this->validator->field('quantity')
            ->validates(NotBlank::class)
            ->validates(Positive::class);
        $this->validator->field('isFragile')
            ->validates(NotBlank::class);
        $this->validator->field('serialNumber')
            ->validates(Length::class, Length::max(100));
        $this->validator->field('lotNumber')
            ->validates(Length::class, Length::max(100));

        return $this->validator;
    }
}
