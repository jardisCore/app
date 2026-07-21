<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Request validator for Category command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class CategoryValidator
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

        $this->validator->field('slug')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(255));
        $this->validator->field('parentIdentifier')
            ->validates(Length::class, Length::max(36));
        $this->validator->field('categoryName')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(255));
        $this->validator->field('metaTitle')
            ->validates(Length::class, Length::max(255));
        $this->validator->field('metaDescription')
            ->validates(Length::class, Length::max(500));
        $this->validator->field('isActive')
            ->validates(NotBlank::class);
        $this->validator->field('isVisible')
            ->validates(NotBlank::class);
        $this->validator->field('sortOrder')
            ->validates(NotBlank::class);
        $this->validator->field('productCount')
            ->validates(NotBlank::class);

        return $this->validator;
    }
}
