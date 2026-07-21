<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity;

use DateTime;
use Ecommerce\Catalog\Data\ProductTaxClass;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * Product data model
 *
 * Generated from table: products
 */
#[Table(name: 'products')]
class Product
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'products';
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
        'sku' => null,
        'name' => null,
        'slug' => null,
        'description' => null,
        'short_description' => null,
        'price' => null,
        'compare_at_price' => null,
        'cost_price' => null,
        'currency' => null,
        'weight_grams' => null,
        'is_active' => null,
        'is_featured' => null,
        'tax_class' => null,
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
    #[Column(name: 'sku', type: 'varchar', length: 100)]
    protected ?string $sku = null;

    /**
     * @var ?string
     */
    #[Column(name: 'name', type: 'varchar', length: 255)]
    protected ?string $name = null;

    /**
     * @var ?string
     */
    #[Column(name: 'slug', type: 'varchar', length: 255)]
    protected ?string $slug = null;

    /**
     * @var ?string
     */
    #[Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    /**
     * @var ?string
     */
    #[Column(name: 'short_description', type: 'varchar', length: 500, nullable: true)]
    protected ?string $shortDescription = null;

    /**
     * @var ?float
     */
    #[Column(name: 'price', type: 'decimal', precision: 10, scale: 2)]
    protected ?float $price = null;

    /**
     * @var ?float
     */
    #[Column(name: 'compare_at_price', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    protected ?float $compareAtPrice = null;

    /**
     * @var ?float
     */
    #[Column(name: 'cost_price', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    protected ?float $costPrice = null;

    /**
     * @var ?string
     */
    #[Column(name: 'currency', type: 'varchar', length: 3)]
    protected ?string $currency = null;

    /**
     * @var ?int
     */
    #[Column(name: 'weight_grams', type: 'int', nullable: true)]
    protected ?int $weightGrams = null;

    /**
     * @var ?int
     */
    #[Column(name: 'is_active', type: 'tinyint')]
    protected ?int $isActive = null;

    /**
     * @var ?int
     */
    #[Column(name: 'is_featured', type: 'tinyint')]
    protected ?int $isFeatured = null;

    /**
     * @var ?ProductTaxClass
     */
    #[Column(name: 'tax_class', type: 'enum')]
    protected ?ProductTaxClass $taxClass = null;

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

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getCompareAtPrice(): ?float
    {
        return $this->compareAtPrice;
    }

    public function getCostPrice(): ?float
    {
        return $this->costPrice;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getWeightGrams(): ?int
    {
        return $this->weightGrams;
    }

    public function getIsActive(): ?int
    {
        return $this->isActive;
    }

    public function getIsFeatured(): ?int
    {
        return $this->isFeatured;
    }

    public function getTaxClass(): ?ProductTaxClass
    {
        return $this->taxClass;
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

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setShortDescription(?string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function setCompareAtPrice(?float $compareAtPrice): self
    {
        $this->compareAtPrice = $compareAtPrice;
        return $this;
    }

    public function setCostPrice(?float $costPrice): self
    {
        $this->costPrice = $costPrice;
        return $this;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function setWeightGrams(?int $weightGrams): self
    {
        $this->weightGrams = $weightGrams;
        return $this;
    }

    public function setIsActive(?int $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function setIsFeatured(?int $isFeatured): self
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function setTaxClass(?ProductTaxClass $taxClass): self
    {
        $this->taxClass = $taxClass;
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
