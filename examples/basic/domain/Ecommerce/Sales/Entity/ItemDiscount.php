<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * ItemDiscount data model
 *
 * Generated from table: item_discounts
 */
#[Table(name: 'item_discounts')]
class ItemDiscount
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'item_discounts';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'item_id' => null,
        'discount_code' => null,
        'amount' => null
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
    #[Column(name: 'item_id', type: 'int')]
    #[ForeignKey(referencedTable: 'order_items', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $itemId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'discount_code', type: 'varchar', length: 50)]
    protected ?string $discountCode = null;

    /**
     * @var ?float
     */
    #[Column(name: 'amount', type: 'decimal', precision: 10, scale: 2)]
    protected ?float $amount = null;

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

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function getDiscountCode(): ?string
    {
        return $this->discountCode;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setItemId(?int $itemId): self
    {
        $this->itemId = $itemId;
        return $this;
    }

    public function setDiscountCode(?string $discountCode): self
    {
        $this->discountCode = $discountCode;
        return $this;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }
}
