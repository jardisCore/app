<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use DateTime;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * PriceTier data model
 *
 * Generated from table: price_tiers
 */
#[Table(name: 'price_tiers')]
class PriceTier
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'price_tiers';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Reflection-Slot jardissupport/data
    protected array $__snapshot = [
        'id' => null,
        'tenant_key' => null,
        'identifier' => null,
        'tier_code' => null,
        'min_quantity' => null,
        'unit_price' => null,
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
    #[Column(name: 'tenant_key', type: 'varchar', length: 36)]
    protected ?string $tenantKey = null;

    /**
     * @var ?string
     */
    #[Column(name: 'identifier', type: 'varchar', length: 36)]
    protected ?string $identifier = null;

    /**
     * @var ?string
     */
    #[Column(name: 'tier_code', type: 'varchar', length: 50)]
    protected ?string $tierCode = null;

    /**
     * @var ?int
     */
    #[Column(name: 'min_quantity', type: 'int')]
    protected ?int $minQuantity = null;

    /**
     * @var ?numeric-string
     */
    #[Column(name: 'unit_price', type: 'decimal', precision: 12, scale: 2)]
    protected ?string $unitPrice = null;

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

    public function getTenantKey(): ?string
    {
        return $this->tenantKey;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getTierCode(): ?string
    {
        return $this->tierCode;
    }

    public function getMinQuantity(): ?int
    {
        return $this->minQuantity;
    }

    /**
     * @return ?numeric-string
     */
    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setTenantKey(?string $tenantKey): self
    {
        $this->tenantKey = $tenantKey;
        return $this;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function setTierCode(?string $tierCode): self
    {
        $this->tierCode = $tierCode;
        return $this;
    }

    public function setMinQuantity(?int $minQuantity): self
    {
        $this->minQuantity = $minQuantity;
        return $this;
    }

    /**
     * @param ?numeric-string $unitPrice
     */
    public function setUnitPrice(?string $unitPrice): self
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
