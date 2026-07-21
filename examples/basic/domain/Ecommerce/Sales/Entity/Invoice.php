<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use DateTime;
use Ecommerce\Sales\Data\InvoicePaymentMethod;
use Ecommerce\Sales\Data\InvoiceStatus;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * Invoice data model
 *
 * Generated from table: invoices
 */
#[Table(name: 'invoices')]
class Invoice
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'invoices';
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
        'invoice_number' => null,
        'order_number' => null,
        'customer_identifier' => null,
        'status' => null,
        'payment_method' => null,
        'total_net' => null,
        'tax_rate' => null,
        'total_gross' => null,
        'currency' => null,
        'note' => null,
        'issued_at' => null,
        'due_at' => null,
        'paid_at' => null,
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
    #[Column(name: 'invoice_number', type: 'varchar', length: 50)]
    protected ?string $invoiceNumber = null;

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
     * @var ?InvoiceStatus
     */
    #[Column(name: 'status', type: 'enum')]
    protected ?InvoiceStatus $status = null;

    /**
     * @var ?InvoicePaymentMethod
     */
    #[Column(name: 'payment_method', type: 'enum', nullable: true)]
    protected ?InvoicePaymentMethod $paymentMethod = null;

    /**
     * @var ?float
     */
    #[Column(name: 'total_net', type: 'decimal', precision: 12, scale: 2)]
    protected ?float $totalNet = null;

    /**
     * @var ?float
     */
    #[Column(name: 'tax_rate', type: 'decimal', precision: 5, scale: 2)]
    protected ?float $taxRate = null;

    /**
     * @var ?float
     */
    #[Column(name: 'total_gross', type: 'decimal', precision: 12, scale: 2)]
    protected ?float $totalGross = null;

    /**
     * @var ?string
     */
    #[Column(name: 'currency', type: 'varchar', length: 3)]
    protected ?string $currency = null;

    /**
     * @var ?string
     */
    #[Column(name: 'note', type: 'text', nullable: true)]
    protected ?string $note = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'issued_at', type: 'datetime', nullable: true)]
    protected ?DateTime $issuedAt = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'due_at', type: 'date', nullable: true)]
    protected ?DateTime $dueAt = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'paid_at', type: 'datetime', nullable: true)]
    protected ?DateTime $paidAt = null;

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

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getCustomerIdentifier(): ?string
    {
        return $this->customerIdentifier;
    }

    public function getStatus(): ?InvoiceStatus
    {
        return $this->status;
    }

    public function getPaymentMethod(): ?InvoicePaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getTotalNet(): ?float
    {
        return $this->totalNet;
    }

    public function getTaxRate(): ?float
    {
        return $this->taxRate;
    }

    public function getTotalGross(): ?float
    {
        return $this->totalGross;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getIssuedAt(): ?DateTime
    {
        return $this->issuedAt;
    }

    public function getDueAt(): ?DateTime
    {
        return $this->dueAt;
    }

    public function getPaidAt(): ?DateTime
    {
        return $this->paidAt;
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

    public function setInvoiceNumber(?string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;
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

    public function setStatus(?InvoiceStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setPaymentMethod(?InvoicePaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function setTotalNet(?float $totalNet): self
    {
        $this->totalNet = $totalNet;
        return $this;
    }

    public function setTaxRate(?float $taxRate): self
    {
        $this->taxRate = $taxRate;
        return $this;
    }

    public function setTotalGross(?float $totalGross): self
    {
        $this->totalGross = $totalGross;
        return $this;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function setIssuedAt(?DateTime $issuedAt): self
    {
        $this->issuedAt = $issuedAt;
        return $this;
    }

    public function setDueAt(?DateTime $dueAt): self
    {
        $this->dueAt = $dueAt;
        return $this;
    }

    public function setPaidAt(?DateTime $paidAt): self
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt ?? new DateTime();
        return $this;
    }
}
