<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * OrderItem data model
 *
 * Generated from table: order_items
 */
#[Table(name: 'order_items')]
class OrderItem
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'order_items';
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
        'identifier' => null,
        'order_id' => null,
        'tenant_key' => null,
        'product_identifier' => null,
        'quantity' => null,
        'unit_price' => null,
        'subtotal' => null
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
    #[Column(name: 'order_id', type: 'int')]
    #[ForeignKey(referencedTable: 'orders', referencedColumn: 'id')]
    protected ?int $orderId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'tenant_key', type: 'varchar', length: 36)]
    protected ?string $tenantKey = null;

    /**
     * @var ?string
     */
    #[Column(name: 'product_identifier', type: 'varchar', length: 36)]
    protected ?string $productIdentifier = null;

    /**
     * @var ?int
     */
    #[Column(name: 'quantity', type: 'int')]
    protected ?int $quantity = null;

    /**
     * @var ?numeric-string
     */
    #[Column(name: 'unit_price', type: 'decimal', precision: 10, scale: 2)]
    protected ?string $unitPrice = null;

    /**
     * @var ?numeric-string
     */
    #[Column(name: 'subtotal', type: 'decimal', precision: 10, scale: 2)]
    protected ?string $subtotal = null;

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

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function getTenantKey(): ?string
    {
        return $this->tenantKey;
    }

    public function getProductIdentifier(): ?string
    {
        return $this->productIdentifier;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * @return ?numeric-string
     */
    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    /**
     * @return ?numeric-string
     */
    public function getSubtotal(): ?string
    {
        return $this->subtotal;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function setOrderId(?int $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function setTenantKey(?string $tenantKey): self
    {
        $this->tenantKey = $tenantKey;
        return $this;
    }

    public function setProductIdentifier(?string $productIdentifier): self
    {
        $this->productIdentifier = $productIdentifier;
        return $this;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
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

    /**
     * @param ?numeric-string $subtotal
     */
    public function setSubtotal(?string $subtotal): self
    {
        $this->subtotal = $subtotal;
        return $this;
    }
}
