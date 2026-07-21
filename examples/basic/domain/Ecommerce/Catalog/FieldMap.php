<?php

declare(strict_types=1);

namespace Ecommerce\Catalog;

/**
 * Central naming map: DTO property names <-> DB column names, per table.
 *
 * Pure naming container (Schema + FieldMap.yaml overrides + default
 * ToPropertyName). One method per BC table:
 *   {table}Columns() — full DTO→column map for FieldMapper::toColumns (write path)
 *
 * The read path (G4 strip + root-id normalization) is aggregate-structural
 * and lives at the aggregate read edge (the query handler), not here.
 *
 * Loaded via $this->handle(FieldMap::class) for ClassVersion support.
 */
class FieldMap
{
    /** @return array<string, string> field -> column mapping */
    public function categoriesColumns(): array
    {
        return [
            'id' => 'id',
            'identifier' => 'identifier',
            'slug' => 'slug',
            'parentIdentifier' => 'parent_identifier',
            'categoryName' => 'name',
            'description' => 'description',
            'metaTitle' => 'meta_title',
            'metaDescription' => 'meta_description',
            'isActive' => 'is_active',
            'isVisible' => 'is_visible',
            'sortOrder' => 'sort_order',
            'productCount' => 'product_count',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function categoryTranslationsColumns(): array
    {
        return [
            'id' => 'id',
            'categoryId' => 'category_id',
            'locale' => 'locale',
            'title' => 'title',
            'description' => 'description',
            'metaTitle' => 'meta_title',
            'metaDescription' => 'meta_description',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function productImagesColumns(): array
    {
        return [
            'id' => 'id',
            'productId' => 'product_id',
            'url' => 'url',
            'altText' => 'alt_text',
            'mimeType' => 'mime_type',
            'fileSize' => 'file_size',
            'width' => 'width',
            'height' => 'height',
            'sortOrder' => 'sort_order',
            'isPrimary' => 'is_primary',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function productVariantPricesColumns(): array
    {
        return [
            'id' => 'id',
            'variantId' => 'variant_id',
            'region' => 'region',
            'price' => 'price',
            'currency' => 'currency',
            'validFrom' => 'valid_from',
            'validUntil' => 'valid_until',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function productVariantsColumns(): array
    {
        return [
            'id' => 'id',
            'identifier' => 'identifier',
            'productId' => 'product_id',
            'sku' => 'sku',
            'variantName' => 'variant_name',
            'optionName' => 'option_name',
            'optionValue' => 'option_value',
            'priceModifier' => 'price_modifier',
            'stockQuantity' => 'stock_quantity',
            'lowStockThreshold' => 'low_stock_threshold',
            'weightGrams' => 'weight_grams',
            'isAvailable' => 'is_available',
            'sortOrder' => 'sort_order',
            'createdAt' => 'created_at',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function productsColumns(): array
    {
        return [
            'id' => 'id',
            'identifier' => 'identifier',
            'sku' => 'sku',
            'productName' => 'name',
            'slug' => 'slug',
            'description' => 'description',
            'shortDescription' => 'short_description',
            'price' => 'price',
            'compareAtPrice' => 'compare_at_price',
            'costPrice' => 'cost_price',
            'currency' => 'currency',
            'weightGrams' => 'weight_grams',
            'isActive' => 'is_active',
            'isFeatured' => 'is_featured',
            'taxClass' => 'tax_class',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
        ];
    }
}
