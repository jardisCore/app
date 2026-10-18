<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Contain;
use JardisSupport\Validation\Validator\Count;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Format;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\NotEmpty;

/**
 * Request validator for Invoice command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL string (no value list, no FK) → NotEmpty (a blank string is empty, R78), other NOT NULL → NotBlank
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
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(50));
        $this->validator->field('orderNumber')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(50));
        $this->validator->field('customerIdentifier')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
            ->validates(Length::class, Length::max(36));
        $this->validator->field('status')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(['draft', 'sent', 'paid', 'overdue', 'cancelled', 'refunded']));
        $this->validator->field('paymentMethod')
            ->validates(Contain::class, Contain::oneOf(['bank_transfer', 'credit_card', 'paypal', 'invoice']));
        $this->validator->field('totalNet')
            ->validates(NotBlank::class)
            ->validates(Format::class, Format::pattern('/^-?\d{1,10}(\.\d{1,2})?$/'));
        $this->validator->field('taxRate')
            ->validates(NotBlank::class)
            ->validates(Format::class, Format::pattern('/^-?\d{1,3}(\.\d{1,2})?$/'));
        $this->validator->field('totalGross')
            ->validates(NotBlank::class)
            ->validates(Format::class, Format::pattern('/^-?\d{1,10}(\.\d{1,2})?$/'));
        $this->validator->field('currency')
            ->validates(NotEmpty::class, NotEmpty::trimmed())
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
