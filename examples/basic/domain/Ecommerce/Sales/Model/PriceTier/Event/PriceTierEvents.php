<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Event;

/**
 * PriceTier Event Registry.
 *
 * Provides access to all PriceTier domain events.
 * Use constants for event subscriptions and getAll() for bulk registration.
 */
class PriceTierEvents
{
    public const PRICE_TIER_CREATED = PriceTierCreated::class;
    public const PRICE_TIER_REMOVED = PriceTierRemoved::class;
    public const PRICE_TIER_UPDATED = PriceTierUpdated::class;

    public static function getAll(): array
    {
        return [
            'PriceTierCreated' => self::PRICE_TIER_CREATED,
            'PriceTierRemoved' => self::PRICE_TIER_REMOVED,
            'PriceTierUpdated' => self::PRICE_TIER_UPDATED,
        ];
    }
}
