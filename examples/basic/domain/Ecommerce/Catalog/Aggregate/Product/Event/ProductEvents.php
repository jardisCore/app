<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

/**
 * Product Event Registry.
 *
 * Provides access to all Product domain events.
 * Use constants for event subscriptions and getAll() for bulk registration.
 */
class ProductEvents
{
    public const PRODUCT_CREATED = ProductCreated::class;
    public const PRODUCT_REMOVED = ProductRemoved::class;
    public const PRODUCT_UPDATED = ProductUpdated::class;
    public const PRODUCT_PRODUCT_IMAGE_ADDED = ProductProductImageAdded::class;
    public const PRODUCT_PRODUCT_IMAGE_REMOVED = ProductProductImageRemoved::class;
    public const PRODUCT_PRODUCT_VARIANT_ADDED = ProductProductVariantAdded::class;
    public const PRODUCT_PRODUCT_VARIANT_REMOVED = ProductProductVariantRemoved::class;
    public const PRODUCT_PRODUCT_VARIANT_PRICE_ADDED = ProductProductVariantPriceAdded::class;
    public const PRODUCT_PRODUCT_VARIANT_PRICE_REMOVED = ProductProductVariantPriceRemoved::class;

    public static function getAll(): array
    {
        return [
            'ProductCreated' => self::PRODUCT_CREATED,
            'ProductRemoved' => self::PRODUCT_REMOVED,
            'ProductUpdated' => self::PRODUCT_UPDATED,
            'ProductProductImageAdded' => self::PRODUCT_PRODUCT_IMAGE_ADDED,
            'ProductProductImageRemoved' => self::PRODUCT_PRODUCT_IMAGE_REMOVED,
            'ProductProductVariantAdded' => self::PRODUCT_PRODUCT_VARIANT_ADDED,
            'ProductProductVariantRemoved' => self::PRODUCT_PRODUCT_VARIANT_REMOVED,
            'ProductProductVariantPriceAdded' => self::PRODUCT_PRODUCT_VARIANT_PRICE_ADDED,
            'ProductProductVariantPriceRemoved' => self::PRODUCT_PRODUCT_VARIANT_PRICE_REMOVED,
        ];
    }
}
