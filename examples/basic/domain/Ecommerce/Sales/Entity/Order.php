<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use DateTime;
use Ecommerce\Sales\Data\OrderStatus;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * Order data model
 *
 * Generated from table: orders
 */
#[Table(name: 'orders')]
class Order
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'orders';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'order_number' => null,
        'customer_id' => null,
        'total_amount' => null,
        'status' => null,
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
    #[Column(name: 'order_number', type: 'varchar', length: 50)]
    protected ?string $orderNumber = null;

    /**
     * @var ?int
     */
    #[Column(name: 'customer_id', type: 'int')]
    #[ForeignKey(referencedTable: 'customers', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $customerId = null;

    /**
     * @var ?float
     */
    #[Column(name: 'total_amount', type: 'decimal', precision: 10, scale: 2)]
    protected ?float $totalAmount = null;

    /**
     * @var ?OrderStatus
     */
    #[Column(name: 'status', type: 'enum')]
    protected ?OrderStatus $status = null;

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

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setOrderNumber(?string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    public function setCustomerId(?int $customerId): self
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function setTotalAmount(?float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function setStatus(?OrderStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt ?? new DateTime();
        return $this;
    }
}
