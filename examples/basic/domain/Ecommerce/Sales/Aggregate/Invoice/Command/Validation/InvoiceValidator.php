<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Contain;
use JardisSupport\Validation\Validator\Count;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Request validator for Invoice command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class InvoiceValidator
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

        $this->validator->field('invoiceNumber')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(50));
        $this->validator->field('orderNumber')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(50));
        $this->validator->field('customerIdentifier')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(36));
        $this->validator->field('status')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(['draft', 'sent', 'paid', 'overdue', 'cancelled', 'refunded']));
        $this->validator->field('paymentMethod')
            ->validates(Contain::class, Contain::oneOf(['bank_transfer', 'credit_card', 'paypal', 'invoice']));
        $this->validator->field('totalNet')
            ->validates(NotBlank::class);
        $this->validator->field('taxRate')
            ->validates(NotBlank::class);
        $this->validator->field('totalGross')
            ->validates(NotBlank::class);
        $this->validator->field('currency')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(3));
        $this->validator->field('issuedAt')
            ->validates(DateTime::class);
        $this->validator->field('dueAt')
            ->validates(DateTime::class);
        $this->validator->field('paidAt')
            ->validates(DateTime::class);
        $this->validator->field('invoiceLine')
            ->validates(Count::class, Count::min(1));

        return $this->validator;
    }
}
