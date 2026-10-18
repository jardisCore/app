<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Entity\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotEmpty;
use JardisSupport\Validation\Validator\PhoneNumber;

/**
 * Validator for ShipmentAddress entity.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL string (no value list, no FK) → NotEmpty (a blank string is empty), other NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - UUID type → Uuid
 * - JSON type → Json
 * - DateTime types → DateTime
 * - Convention-based: email field → Email, url field → Url, etc.
 */
class ShipmentAddressValidator
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

        $this->validator->field('recipientName')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(255));
        $this->validator->field('company')
            ->validates(Length::class, Length::max(255));
        $this->validator->field('street')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(255));
        $this->validator->field('street2')
            ->validates(Length::class, Length::max(255));
        $this->validator->field('city')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(100));
        $this->validator->field('postalCode')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(20));
        $this->validator->field('state')
            ->validates(Length::class, Length::max(100));
        $this->validator->field('country')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(2));
        $this->validator->field('phone')
            ->validates(PhoneNumber::class)
            ->validates(Length::class, Length::max(50));

        return $this->validator;
    }
}
