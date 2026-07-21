<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\Positive;
use JardisSupport\Validation\Validator\Url;

/**
 * Request validator for ProductImage command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class ProductImageValidator
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
