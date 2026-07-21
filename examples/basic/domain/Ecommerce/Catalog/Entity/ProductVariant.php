<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity;

use DateTime;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * ProductVariant data model
 *
 * Generated from table: product_variants
 */
#[Table(name: 'product_variants')]
class ProductVariant
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'product_variants';
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
        'product_id' => null,
        'sku' => null,
        'variant_name' => null,
        'option_name' => null,
        'option_value' => null,
        'price_modifier' => null,
        'stock_quantity' => null,
        'low_stock_threshold' => null,
        'weight_grams' => null,
        'is_available' => null,
        'sort_order' => null,
        'created_at' => 'CURRENT_TIMESTAMP'
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
     * @var ?int
     */
    #[Column(name: 'product_id', type: 'int')]
    #[ForeignKey(referencedTable: 'products', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $productId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'sku', type: 'varchar', length: 100)]
    protected ?string $sku = null;

    /**
     * @var ?string
     */
    #[Column(name: 'variant_name', type: 'varchar', length: 255)]
    protected ?string $variantName = null;

    /**
     * @var ?string
     */
    #[Column(name: 'option_name', type: 'varchar', length: 100)]
    protected ?string $optionName = null;

    /**
     * @var ?string
     */
    #[Column(name: 'option_value', type: 'varchar', length: 100)]
    protected ?string $optionValue = null;

    /**
     * @var ?float
     */
    #[Column(name: 'price_modifier', type: 'decimal', precision: 10, scale: 2)]
    protected ?float $priceModifier = null;

    /**
     * @var ?int
     */
    #[Column(name: 'stock_quantity', type: 'int')]
    protected ?int $stockQuantity = null;

    /**
     * @var ?int
     */
    #[Column(name: 'low_stock_threshold', type: 'int', nullable: true)]
    protected ?int $lowStockThreshold = null;

    /**
     * @var ?int
     */
    #[Column(name: 'weight_grams', type: 'int', nullable: true)]
    protected ?int $weightGrams = null;

    /**
     * @var ?int
     */
    #[Column(name: 'is_available', type: 'tinyint')]
    protected ?int $isAvailable = null;

    /**
     * @var ?int
     */
    #[Column(name: 'sort_order', type: 'int')]
    protected ?int $sortOrder = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'created_at', type: 'timestamp', nullable: true, default: 'CURRENT_TIMESTAMP')]
    protected ?DateTime $createdAt = null;

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

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getVariantName(): ?string
    {
        return $this->variantName;
    }

    public function getOptionName(): ?string
    {
        return $this->optionName;
    }

    public function getOptionValue(): ?string
    {
        return $this->optionValue;
    }

    public function getPriceModifier(): ?float
    {
        return $this->priceModifier;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    public function getLowStockThreshold(): ?int
    {
        return $this->lowStockThreshold;
    }

    public function getWeightGrams(): ?int
    {
        return $this->weightGrams;
    }

    public function getIsAvailable(): ?int
    {
        return $this->isAvailable;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function setProductId(?int $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }

    public function setVariantName(?string $variantName): self
    {
        $this->variantName = $variantName;
        return $this;
    }

    public function setOptionName(?string $optionName): self
    {
        $this->optionName = $optionName;
        return $this;
    }

    public function setOptionValue(?string $optionValue): self
    {
        $this->optionValue = $optionValue;
        return $this;
    }

    public function setPriceModifier(?float $priceModifier): self
    {
        $this->priceModifier = $priceModifier;
        return $this;
    }

    public function setStockQuantity(?int $stockQuantity): self
    {
        $this->stockQuantity = $stockQuantity;
        return $this;
    }

    public function setLowStockThreshold(?int $lowStockThreshold): self
    {
        $this->lowStockThreshold = $lowStockThreshold;
        return $this;
    }

    public function setWeightGrams(?int $weightGrams): self
    {
        $this->weightGrams = $weightGrams;
        return $this;
    }

    public function setIsAvailable(?int $isAvailable): self
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt ?? new DateTime();
        return $this;
    }
}
