<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Event;

/**
 * Category Event Registry.
 *
 * Provides access to all Category domain events.
 * Use constants for event subscriptions and getAll() for bulk registration.
 */
class CategoryEvents
{
    public const CATEGORY_CREATED = CategoryCreated::class;
    public const CATEGORY_REMOVED = CategoryRemoved::class;
    public const CATEGORY_UPDATED = CategoryUpdated::class;
    public const CATEGORY_CATEGORY_TRANSLATION_ADDED = CategoryCategoryTranslationAdded::class;
    public const CATEGORY_CATEGORY_TRANSLATION_REMOVED = CategoryCategoryTranslationRemoved::class;

    public static function getAll(): array
    {
        return [
            'CategoryCreated' => self::CATEGORY_CREATED,
            'CategoryRemoved' => self::CATEGORY_REMOVED,
            'CategoryUpdated' => self::CATEGORY_UPDATED,
            'CategoryCategoryTranslationAdded' => self::CATEGORY_CATEGORY_TRANSLATION_ADDED,
            'CategoryCategoryTranslationRemoved' => self::CATEGORY_CATEGORY_TRANSLATION_REMOVED,
        ];
    }
}
