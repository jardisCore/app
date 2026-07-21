<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\Positive;

/**
 * Validator for ProductVariant entity.
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
class ProductVariantValidator
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
        $this->validator->field('createdAt')
            ->validates(DateTime::class);

        return $this->validator;
    }
}
