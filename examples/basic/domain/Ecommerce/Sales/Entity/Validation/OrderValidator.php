<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity\Validation;

use Ecommerce\Sales\Data\OrderStatus;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Contain;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Validator for Order entity.
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
class OrderValidator
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

        $this->validator->field('orderNumber')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(50));
        $this->validator->field('totalAmount')
            ->validates(NotBlank::class);
        $this->validator->field('status')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(OrderStatus::cases()));
        $this->validator->field('createdAt')
            ->validates(DateTime::class);

        return $this->validator;
    }
}
