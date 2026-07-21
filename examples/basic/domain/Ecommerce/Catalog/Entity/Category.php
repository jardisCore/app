<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity;

use DateTime;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * Category data model
 *
 * Generated from table: categories
 */
#[Table(name: 'categories')]
class Category
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'categories';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'identifier' => null,
        'slug' => null,
        'parent_identifier' => null,
        'name' => null,
        'description' => null,
        'meta_title' => null,
        'meta_description' => null,
        'is_active' => null,
        'is_visible' => null,
        'sort_order' => null,
        'product_count' => null,
        'created_at' => 'CURRENT_TIMESTAMP',
        'updated_at' => null
    ];

    /**
     * @var ?int
     */
    #[PrimaryKey(autoIncrement: true)]
    #[Column(name: 'id', type: 'int')]
    protected ?int $id = null;

    /**
     * @var ?string
     */
    #[Column(name: 'identifier', type: 'varchar', length: 36)]
    protected ?string $identifier = null;

    /**
     * @var ?string
     */
    #[Column(name: 'slug', type: 'varchar', length: 255)]
    protected ?string $slug = null;

    /**
     * @var ?string
     */
    #[Column(name: 'parent_identifier', type: 'varchar', length: 36, nullable: true)]
    protected ?string $parentIdentifier = null;

    /**
     * @var ?string
     */
    #[Column(name: 'name', type: 'varchar', length: 255)]
    protected ?string $name = null;

    /**
     * @var ?string
     */
    #[Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    /**
     * @var ?string
     */
    #[Column(name: 'meta_title', type: 'varchar', length: 255, nullable: true)]
    protected ?string $metaTitle = null;

    /**
     * @var ?string
     */
    #[Column(name: 'meta_description', type: 'varchar', length: 500, nullable: true)]
    protected ?string $metaDescription = null;

    /**
     * @var ?int
     */
    #[Column(name: 'is_active', type: 'tinyint')]
    protected ?int $isActive = null;

    /**
     * @var ?int
     */
    #[Column(name: 'is_visible', type: 'tinyint')]
    protected ?int $isVisible = null;

    /**
     * @var ?int
     */
    #[Column(name: 'sort_order', type: 'int')]
    protected ?int $sortOrder = null;

    /**
     * @var ?int
     */
    #[Column(name: 'product_count', type: 'int')]
    protected ?int $productCount = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'created_at', type: 'timestamp', nullable: true, default: 'CURRENT_TIMESTAMP')]
    protected ?DateTime $createdAt = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'updated_at', type: 'datetime', nullable: true)]
    protected ?DateTime $updatedAt = null;

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

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getParentIdentifier(): ?string
    {
        return $this->parentIdentifier;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function getIsActive(): ?int
    {
        return $this->isActive;
    }

    public function getIsVisible(): ?int
    {
        return $this->isVisible;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function getProductCount(): ?int
    {
        return $this->productCount;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setParentIdentifier(?string $parentIdentifier): self
    {
        $this->parentIdentifier = $parentIdentifier;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function setIsActive(?int $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function setIsVisible(?int $isVisible): self
    {
        $this->isVisible = $isVisible;
        return $this;
    }

    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function setProductCount(?int $productCount): self
    {
        $this->productCount = $productCount;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt ?? new DateTime();
        return $this;
    }

    public function setUpdatedAt(?DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
