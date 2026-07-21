<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Entity;

use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * ShipmentItem data model
 *
 * Generated from table: shipment_items
 */
#[Table(name: 'shipment_items')]
class ShipmentItem
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'shipment_items';
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
        'shipment_id' => null,
        'product_sku' => null,
        'product_name' => null,
        'quantity' => null,
        'weight_grams' => null,
        'is_fragile' => null,
        'serial_number' => null,
        'lot_number' => null
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
    #[Column(name: 'shipment_id', type: 'int')]
    #[ForeignKey(referencedTable: 'shipments', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $shipmentId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'product_sku', type: 'varchar', length: 100)]
    protected ?string $productSku = null;

    /**
     * @var ?string
     */
    #[Column(name: 'product_name', type: 'varchar', length: 255)]
    protected ?string $productName = null;

    /**
     * @var ?int
     */
    #[Column(name: 'quantity', type: 'int')]
    protected ?int $quantity = null;

    /**
     * @var ?int
     */
    #[Column(name: 'weight_grams', type: 'int', nullable: true)]
    protected ?int $weightGrams = null;

    /**
     * @var ?int
     */
    #[Column(name: 'is_fragile', type: 'tinyint')]
    protected ?int $isFragile = null;

    /**
     * @var ?string
     */
    #[Column(name: 'serial_number', type: 'varchar', length: 100, nullable: true)]
    protected ?string $serialNumber = null;

    /**
     * @var ?string
     */
    #[Column(name: 'lot_number', type: 'varchar', length: 100, nullable: true)]
    protected ?string $lotNumber = null;

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

    public function getShipmentId(): ?int
    {
        return $this->shipmentId;
    }

    public function getProductSku(): ?string
    {
        return $this->productSku;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function getWeightGrams(): ?int
    {
        return $this->weightGrams;
    }

    public function getIsFragile(): ?int
    {
        return $this->isFragile;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function getLotNumber(): ?string
    {
        return $this->lotNumber;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function setShipmentId(?int $shipmentId): self
    {
        $this->shipmentId = $shipmentId;
        return $this;
    }

    public function setProductSku(?string $productSku): self
    {
        $this->productSku = $productSku;
        return $this;
    }

    public function setProductName(?string $productName): self
    {
        $this->productName = $productName;
        return $this;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function setWeightGrams(?int $weightGrams): self
    {
        $this->weightGrams = $weightGrams;
        return $this;
    }

    public function setIsFragile(?int $isFragile): self
    {
        $this->isFragile = $isFragile;
        return $this;
    }

    public function setSerialNumber(?string $serialNumber): self
    {
        $this->serialNumber = $serialNumber;
        return $this;
    }

    public function setLotNumber(?string $lotNumber): self
    {
        $this->lotNumber = $lotNumber;
        return $this;
    }
}
