<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Validator for ProductVariantPrice entity.
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
class ProductVariantPriceValidator
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

        $this->validator->field('region')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(10));
        $this->validator->field('price')
            ->validates(NotBlank::class);
        $this->validator->field('currency')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(3));
        $this->validator->field('validFrom')
            ->validates(DateTime::class);
        $this->validator->field('validUntil')
            ->validates(DateTime::class);

        return $this->validator;
    }
}
