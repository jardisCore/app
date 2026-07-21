<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Count;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\Positive;

/**
 * Request validator for ProductVariant command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class ProductVariantValidator
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

        $this->validator->field('sku')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(100));
        $this->validator->field('variantName')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(255));
        $this->validator->field('optionName')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(100));
        $this->validator->field('optionValue')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(100));
        $this->validator->field('priceModifier')
            ->validates(NotBlank::class);
        $this->validator->field('stockQuantity')
            ->validates(NotBlank::class)
            ->validates(Positive::class);
        $this->validator->field('lowStockThreshold')
            ->validates(Positive::class);
        $this->validator->field('isAvailable')
            ->validates(NotBlank::class);
        $this->validator->field('sortOrder')
            ->validates(NotBlank::class);
        $this->validator->field('productVariantPrice')
            ->validates(Count::class, Count::min(1));

        return $this->validator;
    }
}
