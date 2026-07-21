<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Validator for Category entity.
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
class CategoryValidator
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

        $this->validator->field('slug')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(255));
        $this->validator->field('parentIdentifier')
            ->validates(Length::class, Length::max(36));
        $this->validator->field('name')
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
        $this->validator->field('createdAt')
            ->validates(DateTime::class);
        $this->validator->field('updatedAt')
            ->validates(DateTime::class);

        return $this->validator;
    }
}
