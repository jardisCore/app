<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\Positive;

/**
 * Request validator for OrderItem command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class OrderItemValidator
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

        $this->validator->field('productIdentifier')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(36));
        $this->validator->field('quantity')
            ->validates(NotBlank::class)
            ->validates(Positive::class);
        $this->validator->field('unitPrice')
            ->validates(NotBlank::class);
        $this->validator->field('subtotal')
            ->validates(NotBlank::class);

        return $this->validator;
    }
}
