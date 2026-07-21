<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Email;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\PhoneNumber;

/**
 * Validator for Customer entity.
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
class CustomerValidator
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

        $this->validator->field('email')
            ->validates(NotBlank::class)
            ->validates(Email::class)
            ->validates(Length::class, Length::max(255));
        $this->validator->field('name')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(255));
        $this->validator->field('phone')
            ->validates(PhoneNumber::class)
            ->validates(Length::class, Length::max(50));
        $this->validator->field('createdAt')
            ->validates(DateTime::class);

        return $this->validator;
    }
}
