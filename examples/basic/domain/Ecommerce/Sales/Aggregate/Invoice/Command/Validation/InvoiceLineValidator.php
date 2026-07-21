<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Request validator for InvoiceLine command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class InvoiceLineValidator
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

        $this->validator->field('position')
            ->validates(NotBlank::class);
        $this->validator->field('description')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(500));
        $this->validator->field('quantity')
            ->validates(NotBlank::class);
        $this->validator->field('unit')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(20));
        $this->validator->field('unitPrice')
            ->validates(NotBlank::class);
        $this->validator->field('lineTotal')
            ->validates(NotBlank::class);
        $this->validator->field('taxIncluded')
            ->validates(NotBlank::class);

        return $this->validator;
    }
}
