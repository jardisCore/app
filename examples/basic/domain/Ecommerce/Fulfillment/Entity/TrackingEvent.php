<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Entity;

use DateTime;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * TrackingEvent data model
 *
 * Generated from table: tracking_events
 */
#[Table(name: 'tracking_events')]
class TrackingEvent
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'tracking_events';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'shipment_id' => null,
        'event_code' => null,
        'status' => null,
        'location' => null,
        'postal_code' => null,
        'detail' => null,
        'occurred_at' => null,
        'reported_at' => null
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
    #[Column(name: 'shipment_id', type: 'int')]
    #[ForeignKey(referencedTable: 'shipments', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $shipmentId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'event_code', type: 'varchar', length: 50)]
    protected ?string $eventCode = null;

    /**
     * @var ?string
     */
    #[Column(name: 'status', type: 'varchar', length: 100)]
    protected ?string $status = null;

    /**
     * @var ?string
     */
    #[Column(name: 'location', type: 'varchar', length: 255, nullable: true)]
    protected ?string $location = null;

    /**
     * @var ?string
     */
    #[Column(name: 'postal_code', type: 'varchar', length: 20, nullable: true)]
    protected ?string $postalCode = null;

    /**
     * @var ?string
     */
    #[Column(name: 'detail', type: 'text', nullable: true)]
    protected ?string $detail = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'occurred_at', type: 'datetime')]
    protected ?DateTime $occurredAt = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'reported_at', type: 'datetime')]
    protected ?DateTime $reportedAt = null;

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

    public function getShipmentId(): ?int
    {
        return $this->shipmentId;
    }

    public function getEventCode(): ?string
    {
        return $this->eventCode;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function getOccurredAt(): ?DateTime
    {
        return $this->occurredAt;
    }

    public function getReportedAt(): ?DateTime
    {
        return $this->reportedAt;
    }

    public function setShipmentId(?int $shipmentId): self
    {
        $this->shipmentId = $shipmentId;
        return $this;
    }

    public function setEventCode(?string $eventCode): self
    {
        $this->eventCode = $eventCode;
        return $this;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function setDetail(?string $detail): self
    {
        $this->detail = $detail;
        return $this;
    }

    public function setOccurredAt(?DateTime $occurredAt): self
    {
        $this->occurredAt = $occurredAt;
        return $this;
    }

    public function setReportedAt(?DateTime $reportedAt): self
    {
        $this->reportedAt = $reportedAt;
        return $this;
    }
}
