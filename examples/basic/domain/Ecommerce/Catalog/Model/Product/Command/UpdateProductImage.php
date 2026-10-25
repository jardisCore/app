<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command;

/**
 * Command data class for UpdateProductImage.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateProductImage
{
    public function __construct(
        public string $tenant,
        public string $productIdentifier,
        public int $productImageId,
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
