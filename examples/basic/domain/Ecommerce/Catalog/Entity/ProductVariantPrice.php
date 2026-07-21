<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity;

use DateTime;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * ProductVariantPrice data model
 *
 * Generated from table: product_variant_prices
 */
#[Table(name: 'product_variant_prices')]
class ProductVariantPrice
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'product_variant_prices';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'variant_id' => null,
        'region' => null,
        'price' => null,
        'currency' => null,
        'valid_from' => null,
        'valid_until' => null
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
    #[Column(name: 'variant_id', type: 'int')]
    #[ForeignKey(referencedTable: 'product_variants', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $variantId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'region', type: 'varchar', length: 10)]
    protected ?string $region = null;

    /**
     * @var ?float
     */
    #[Column(name: 'price', type: 'decimal', precision: 10, scale: 2)]
    protected ?float $price = null;

    /**
     * @var ?string
     */
    #[Column(name: 'currency', type: 'varchar', length: 3)]
    protected ?string $currency = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'valid_from', type: 'date', nullable: true)]
    protected ?DateTime $validFrom = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'valid_until', type: 'date', nullable: true)]
    protected ?DateTime $validUntil = null;

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

    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getValidFrom(): ?DateTime
    {
        return $this->validFrom;
    }

    public function getValidUntil(): ?DateTime
    {
        return $this->validUntil;
    }

    public function setVariantId(?int $variantId): self
    {
        $this->variantId = $variantId;
        return $this;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function setValidFrom(?DateTime $validFrom): self
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    public function setValidUntil(?DateTime $validUntil): self
    {
        $this->validUntil = $validUntil;
        return $this;
    }
}
