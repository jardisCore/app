<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity;

use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * ProductImage data model
 *
 * Generated from table: product_images
 */
#[Table(name: 'product_images')]
class ProductImage
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'product_images';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'product_id' => null,
        'url' => null,
        'alt_text' => null,
        'mime_type' => null,
        'file_size' => null,
        'width' => null,
        'height' => null,
        'sort_order' => null,
        'is_primary' => null
    ];

    /**
     * @var ?int
     */
    #[PrimaryKey(autoIncrement: true)]
    #[Column(name: 'id', type: 'int')]
    protected ?int $id = null;

    /**
     * @var ?int
     */
    #[Column(name: 'product_id', type: 'int')]
    #[ForeignKey(referencedTable: 'products', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $productId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'url', type: 'varchar', length: 500)]
    protected ?string $url = null;

    /**
     * @var ?string
     */
    #[Column(name: 'alt_text', type: 'varchar', length: 255, nullable: true)]
    protected ?string $altText = null;

    /**
     * @var ?string
     */
    #[Column(name: 'mime_type', type: 'varchar', length: 50)]
    protected ?string $mimeType = null;

    /**
     * @var ?int
     */
    #[Column(name: 'file_size', type: 'int', nullable: true)]
    protected ?int $fileSize = null;

    /**
     * @var ?int
     */
    #[Column(name: 'width', type: 'int', nullable: true)]
    protected ?int $width = null;

    /**
     * @var ?int
     */
    #[Column(name: 'height', type: 'int', nullable: true)]
    protected ?int $height = null;

    /**
     * @var ?int
     */
    #[Column(name: 'sort_order', type: 'int')]
    protected ?int $sortOrder = null;

    /**
     * @var ?int
     */
    #[Column(name: 'is_primary', type: 'tinyint')]
    protected ?int $isPrimary = null;

    /**
     * Returns the snapshot of original values.
     *
     * @return array<string, mixed>
     */
    public function getSnapshot(): array
    {
        return $this->__snapshot;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function getIsPrimary(): ?int
    {
        return $this->isPrimary;
    }

    public function setProductId(?int $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function setAltText(?string $altText): self
    {
        $this->altText = $altText;
        return $this;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function setWidth(?int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function setIsPrimary(?int $isPrimary): self
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }
}
