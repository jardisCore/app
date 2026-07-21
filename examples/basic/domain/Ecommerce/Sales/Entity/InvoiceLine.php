<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use DateTime;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * InvoiceLine data model
 *
 * Generated from table: invoice_lines
 */
#[Table(name: 'invoice_lines')]
class InvoiceLine
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'invoice_lines';
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
        'invoice_id' => null,
        'position' => null,
        'description' => null,
        'quantity' => null,
        'unit' => null,
        'unit_price' => null,
        'discount_percent' => null,
        'line_total' => null,
        'tax_included' => null,
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
    #[Column(name: 'invoice_id', type: 'int')]
    #[ForeignKey(referencedTable: 'invoices', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $invoiceId = null;

    /**
     * @var ?int
     */
    #[Column(name: 'position', type: 'int')]
    protected ?int $position = null;

    /**
     * @var ?string
     */
    #[Column(name: 'description', type: 'varchar', length: 500)]
    protected ?string $description = null;

    /**
     * @var ?float
     */
    #[Column(name: 'quantity', type: 'decimal', precision: 10, scale: 3)]
    protected ?float $quantity = null;

    /**
     * @var ?string
     */
    #[Column(name: 'unit', type: 'varchar', length: 20)]
    protected ?string $unit = null;

    /**
     * @var ?float
     */
    #[Column(name: 'unit_price', type: 'decimal', precision: 12, scale: 4)]
    protected ?float $unitPrice = null;

    /**
     * @var ?float
     */
    #[Column(name: 'discount_percent', type: 'decimal', precision: 5, scale: 2, nullable: true)]
    protected ?float $discountPercent = null;

    /**
     * @var ?float
     */
    #[Column(name: 'line_total', type: 'decimal', precision: 12, scale: 2)]
    protected ?float $lineTotal = null;

    /**
     * @var ?int
     */
    #[Column(name: 'tax_included', type: 'tinyint')]
    protected ?int $taxIncluded = null;

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

    public function getInvoiceId(): ?int
    {
        return $this->invoiceId;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function getDiscountPercent(): ?float
    {
        return $this->discountPercent;
    }

    public function getLineTotal(): ?float
    {
        return $this->lineTotal;
    }

    public function getTaxIncluded(): ?int
    {
        return $this->taxIncluded;
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

    public function setInvoiceId(?int $invoiceId): self
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setQuantity(?float $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function setUnitPrice(?float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function setDiscountPercent(?float $discountPercent): self
    {
        $this->discountPercent = $discountPercent;
        return $this;
    }

    public function setLineTotal(?float $lineTotal): self
    {
        $this->lineTotal = $lineTotal;
        return $this;
    }

    public function setTaxIncluded(?int $taxIncluded): self
    {
        $this->taxIncluded = $taxIncluded;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt ?? new DateTime();
        return $this;
    }
}
