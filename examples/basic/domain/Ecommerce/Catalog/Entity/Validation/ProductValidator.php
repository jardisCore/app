<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity\Validation;

use Ecommerce\Catalog\Data\ProductTaxClass;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Contain;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Validator for Product entity.
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
class ProductValidator
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
        $this->validator->field('name')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(255));
        $this->validator->field('slug')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(255));
        $this->validator->field('shortDescription')
            ->validates(Length::class, Length::max(500));
        $this->validator->field('price')
            ->validates(NotBlank::class);
        $this->validator->field('currency')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(3));
        $this->validator->field('isActive')
            ->validates(NotBlank::class);
        $this->validator->field('isFeatured')
            ->validates(NotBlank::class);
        $this->validator->field('taxClass')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(ProductTaxClass::cases()));
        $this->validator->field('createdAt')
            ->validates(DateTime::class);
        $this->validator->field('updatedAt')
            ->validates(DateTime::class);

        return $this->validator;
    }
}
