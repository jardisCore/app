<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Format;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\NotEmpty;

/**
 * Validator for InvoiceLine entity.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL string (no value list, no FK) → NotEmpty (a blank string is empty), other NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - UUID type → Uuid
 * - JSON type → Json
 * - DateTime types → DateTime
 * - Convention-based: email field → Email, url field → Url, etc.
 */
class InvoiceLineValidator
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

        $this->validator->field('position')
            ->validates(NotBlank::class);
        $this->validator->field('description')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(500));
        $this->validator->field('quantity')
            ->validates(NotBlank::class)
            ->validates(Format::class, Format::pattern('/^-?\d{1,7}(\.\d{1,3})?$/'));
        $this->validator->field('unit')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(20));
        $this->validator->field('unitPrice')
            ->validates(NotBlank::class)
            ->validates(Format::class, Format::pattern('/^-?\d{1,8}(\.\d{1,4})?$/'));
        $this->validator->field('discountPercent')
            ->validates(Format::class, Format::pattern('/^-?\d{1,3}(\.\d{1,2})?$/'));
        $this->validator->field('lineTotal')
            ->validates(NotBlank::class)
            ->validates(Format::class, Format::pattern('/^-?\d{1,10}(\.\d{1,2})?$/'));
        $this->validator->field('taxIncluded')
            ->validates(NotBlank::class);
        $this->validator->field('createdAt')
            ->validates(DateTime::class);

        return $this->validator;
    }
}
