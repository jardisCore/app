<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\Positive;
use JardisSupport\Validation\Validator\Url;

/**
 * Validator for ProductImage entity.
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
class ProductImageValidator
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

        $this->validator->field('url')
            ->validates(NotBlank::class)
            ->validates(Url::class)
            ->validates(Length::class, Length::max(500));
        $this->validator->field('altText')
            ->validates(Length::class, Length::max(255));
        $this->validator->field('mimeType')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(50));
        $this->validator->field('fileSize')
            ->validates(Positive::class);
        $this->validator->field('sortOrder')
            ->validates(NotBlank::class);
        $this->validator->field('isPrimary')
            ->validates(NotBlank::class);

        return $this->validator;
    }
}
