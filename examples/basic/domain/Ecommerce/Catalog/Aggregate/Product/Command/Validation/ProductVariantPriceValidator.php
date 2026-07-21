<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Request validator for ProductVariantPrice command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class ProductVariantPriceValidator
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
