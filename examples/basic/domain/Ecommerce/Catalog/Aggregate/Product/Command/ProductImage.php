<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command;

/**
 * Command data class for ProductImage.
 *
 * Readonly DTO for command operations.
 */
readonly class ProductImage
{
    public function __construct(
        public string $url,
        public ?string $altText,
        public string $mimeType,
        public ?int $fileSize,
        public ?int $width,
        public ?int $height,
        public int $sortOrder,
        public int $isPrimary
    ) {
    }
}
