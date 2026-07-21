<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Entity\Validation;

use Ecommerce\Fulfillment\Data\ShipmentCarrier;
use Ecommerce\Fulfillment\Data\ShipmentServiceLevel;
use Ecommerce\Fulfillment\Data\ShipmentStatus;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Contain;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;

/**
 * Validator for Shipment entity.
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
class ShipmentValidator
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
        $this->validator->field('customerIdentifier')
            ->validates(NotBlank::class)
            ->validates(Length::class, Length::max(36));
        $this->validator->field('carrier')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(ShipmentCarrier::cases()));
        $this->validator->field('serviceLevel')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(ShipmentServiceLevel::cases()));
        $this->validator->field('trackingNumber')
            ->validates(Length::class, Length::max(100));
        $this->validator->field('status')
            ->validates(NotBlank::class)
            ->validates(Contain::class, Contain::oneOf(ShipmentStatus::cases()));
        $this->validator->field('packageCount')
            ->validates(NotBlank::class);
        $this->validator->field('estimatedDelivery')
            ->validates(DateTime::class);
        $this->validator->field('shippedAt')
            ->validates(DateTime::class);
        $this->validator->field('deliveredAt')
            ->validates(DateTime::class);
        $this->validator->field('createdAt')
            ->validates(DateTime::class);

        return $this->validator;
    }
}
