<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Entity;

use DateTime;
use Ecommerce\Fulfillment\Data\ShipmentCarrier;
use Ecommerce\Fulfillment\Data\ShipmentServiceLevel;
use Ecommerce\Fulfillment\Data\ShipmentStatus;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * Shipment data model
 *
 * Generated from table: shipments
 */
#[Table(name: 'shipments')]
class Shipment
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'shipments';
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
        'order_number' => null,
        'customer_identifier' => null,
        'delivery_address_id' => null,
        'carrier' => null,
        'service_level' => null,
        'tracking_number' => null,
        'status' => null,
        'weight_grams' => null,
        'package_count' => null,
        'insurance_value' => null,
        'estimated_delivery' => null,
        'shipped_at' => null,
        'delivered_at' => null,
        'note' => null,
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
     * @var ?string
     */
    #[Column(name: 'order_number', type: 'varchar', length: 50)]
    protected ?string $orderNumber = null;

    /**
     * @var ?string
     */
    #[Column(name: 'customer_identifier', type: 'varchar', length: 36)]
    protected ?string $customerIdentifier = null;

    /**
     * @var ?int
     */
    #[Column(name: 'delivery_address_id', type: 'int')]
    #[ForeignKey(referencedTable: 'shipment_addresses', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $deliveryAddressId = null;

    /**
     * @var ?ShipmentCarrier
     */
    #[Column(name: 'carrier', type: 'enum')]
    protected ?ShipmentCarrier $carrier = null;

    /**
     * @var ?ShipmentServiceLevel
     */
    #[Column(name: 'service_level', type: 'enum')]
    protected ?ShipmentServiceLevel $serviceLevel = null;

    /**
     * @var ?string
     */
    #[Column(name: 'tracking_number', type: 'varchar', length: 100, nullable: true)]
    protected ?string $trackingNumber = null;

    /**
     * @var ?ShipmentStatus
     */
    #[Column(name: 'status', type: 'enum')]
    protected ?ShipmentStatus $status = null;

    /**
     * @var ?int
     */
    #[Column(name: 'weight_grams', type: 'int', nullable: true)]
    protected ?int $weightGrams = null;

    /**
     * @var ?int
     */
    #[Column(name: 'package_count', type: 'int')]
    protected ?int $packageCount = null;

    /**
     * @var ?float
     */
    #[Column(name: 'insurance_value', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    protected ?float $insuranceValue = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'estimated_delivery', type: 'date', nullable: true)]
    protected ?DateTime $estimatedDelivery = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'shipped_at', type: 'datetime', nullable: true)]
    protected ?DateTime $shippedAt = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'delivered_at', type: 'datetime', nullable: true)]
    protected ?DateTime $deliveredAt = null;

    /**
     * @var ?string
     */
    #[Column(name: 'note', type: 'text', nullable: true)]
    protected ?string $note = null;

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

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getCustomerIdentifier(): ?string
    {
        return $this->customerIdentifier;
    }

    public function getDeliveryAddressId(): ?int
    {
        return $this->deliveryAddressId;
    }

    public function getCarrier(): ?ShipmentCarrier
    {
        return $this->carrier;
    }

    public function getServiceLevel(): ?ShipmentServiceLevel
    {
        return $this->serviceLevel;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function getStatus(): ?ShipmentStatus
    {
        return $this->status;
    }

    public function getWeightGrams(): ?int
    {
        return $this->weightGrams;
    }

    public function getPackageCount(): ?int
    {
        return $this->packageCount;
    }

    public function getInsuranceValue(): ?float
    {
        return $this->insuranceValue;
    }

    public function getEstimatedDelivery(): ?DateTime
    {
        return $this->estimatedDelivery;
    }

    public function getShippedAt(): ?DateTime
    {
        return $this->shippedAt;
    }

    public function getDeliveredAt(): ?DateTime
    {
        return $this->deliveredAt;
    }

    public function getNote(): ?string
    {
        return $this->note;
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

    public function setOrderNumber(?string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    public function setCustomerIdentifier(?string $customerIdentifier): self
    {
        $this->customerIdentifier = $customerIdentifier;
        return $this;
    }

    public function setDeliveryAddressId(?int $deliveryAddressId): self
    {
        $this->deliveryAddressId = $deliveryAddressId;
        return $this;
    }

    public function setCarrier(?ShipmentCarrier $carrier): self
    {
        $this->carrier = $carrier;
        return $this;
    }

    public function setServiceLevel(?ShipmentServiceLevel $serviceLevel): self
    {
        $this->serviceLevel = $serviceLevel;
        return $this;
    }

    public function setTrackingNumber(?string $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function setStatus(?ShipmentStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setWeightGrams(?int $weightGrams): self
    {
        $this->weightGrams = $weightGrams;
        return $this;
    }

    public function setPackageCount(?int $packageCount): self
    {
        $this->packageCount = $packageCount;
        return $this;
    }

    public function setInsuranceValue(?float $insuranceValue): self
    {
        $this->insuranceValue = $insuranceValue;
        return $this;
    }

    public function setEstimatedDelivery(?DateTime $estimatedDelivery): self
    {
        $this->estimatedDelivery = $estimatedDelivery;
        return $this;
    }

    public function setShippedAt(?DateTime $shippedAt): self
    {
        $this->shippedAt = $shippedAt;
        return $this;
    }

    public function setDeliveredAt(?DateTime $deliveredAt): self
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt ?? new DateTime();
        return $this;
    }
}
