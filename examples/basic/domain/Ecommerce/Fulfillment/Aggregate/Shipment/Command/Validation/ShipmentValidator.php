<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Contain;
use JardisSupport\Validation\Validator\Count;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Request validator for Shipment command DTO.
 *
 * Auto-generated validation rules from schema metadata:
 * - NOT NULL → NotBlank
 * - VARCHAR(n) → Length::max()
 * - Numeric constraints → Range::between() or Positive
 * - ENUM → Contain::oneOf()
 * - Convention-based: email → Email, url → Url, etc.
 */
class ShipmentValidator
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

        $this->validator->field('orderNumber')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(50));
        $this->validator->field('customerIdentifier')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(36));
        $this->validator->field('carrier')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(['dhl', 'ups', 'fedex', 'dpd', 'hermes', 'gls']));
        $this->validator->field('serviceLevel')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(['standard', 'express', 'overnight', 'economy']));
        $this->validator->field('trackingNumber')
            ->validates(Length::class, Length::max(100));
        $this->validator->field('status')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(['pending', 'confirmed', 'picking', 'packed', 'shipped', 'in_transit', 'delivered', 'failed']));
        $this->validator->field('packageCount')
            ->validates(NotBlank::class);
        $this->validator->field('estimatedDelivery')
            ->validates(DateTime::class);
        $this->validator->field('shippedAt')
            ->validates(DateTime::class);
        $this->validator->field('deliveredAt')
            ->validates(DateTime::class);
        $this->validator->field('shipmentItem')
            ->validates(Count::class, Count::min(1));

        return $this->validator;
    }
}
