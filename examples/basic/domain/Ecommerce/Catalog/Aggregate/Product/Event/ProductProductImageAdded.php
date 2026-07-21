<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use DateTimeImmutable;

/**
 * Event: ProductImage was added.
 *
 * Dispatched after a new ProductImage is added to the aggregate.
 */
readonly class ProductProductImageAdded
{
    /**
     * @param string $productIdentifier
     * @param int $productImageId
     * @param string $url
     * @param ?string $altText
     * @param string $mimeType
     * @param ?int $fileSize
     * @param ?int $width
     * @param ?int $height
     * @param int $sortOrder
     * @param int $isPrimary
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public int $productImageId,
        public string $url,
        public ?string $altText,
        public string $mimeType,
        public ?int $fileSize,
        public ?int $width,
        public ?int $height,
        public int $sortOrder,
        public int $isPrimary,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
