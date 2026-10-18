<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Contain;
use JardisSupport\Validation\Validator\Count;
use JardisSupport\Validation\Validator\Format;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\NotEmpty;

/**
 * Request validator for Product command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL string (no value list, no FK) → NotEmpty (a blank string is empty, R78), other NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class ProductValidator
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
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(100));
        $this->validator->field('productName')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(255));
        $this->validator->field('slug')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(255));
        $this->validator->field('shortDescription')
            ->validates(Length::class, Length::max(500));
        $this->validator->field('price')
            ->validates(NotBlank::class)
            ->validates(Format::class, Format::pattern('/^-?\d{1,8}(\.\d{1,2})?$/'));
        $this->validator->field('compareAtPrice')
            ->validates(Format::class, Format::pattern('/^-?\d{1,8}(\.\d{1,2})?$/'));
        $this->validator->field('costPrice')
            ->validates(Format::class, Format::pattern('/^-?\d{1,8}(\.\d{1,2})?$/'));
        $this->validator->field('currency')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(3));
        $this->validator->field('isActive')
            ->validates(NotBlank::class);
        $this->validator->field('isFeatured')
            ->validates(NotBlank::class);
        $this->validator->field('taxClass')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(['standard', 'reduced', 'zero']));
        $this->validator->field('productVariant')
            ->validates(Count::class, Count::min(1));

        return $this->validator;
    }
}
