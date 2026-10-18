<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use DateTime;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * Voucher data model
 *
 * Generated from table: vouchers
 */
#[Table(name: 'vouchers')]
class Voucher
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'vouchers';
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
        'scope_key' => null,
        'voucher_code' => null,
        'amount' => null,
        'note' => null,
        'secret_digest' => null,
        'issued_by' => null,
        'issuer_id' => null,
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
    #[Column(name: 'scope_key', type: 'varchar', length: 36)]
    protected ?string $scopeKey = null;

    /**
     * @var ?string
     */
    #[Column(name: 'voucher_code', type: 'varchar', length: 50)]
    protected ?string $voucherCode = null;

    /**
     * @var ?numeric-string
     */
    #[Column(name: 'amount', type: 'decimal', precision: 10, scale: 2)]
    protected ?string $amount = null;

    /**
     * @var ?string
     */
    #[Column(name: 'note', type: 'varchar', length: 255, nullable: true)]
    protected ?string $note = null;

    /**
     * @var ?string
     */
    #[Column(name: 'secret_digest', type: 'varchar', length: 64)]
    protected ?string $secretDigest = null;

    /**
     * @var ?string
     */
    #[Column(name: 'issued_by', type: 'varchar', length: 36)]
    protected ?string $issuedBy = null;

    /**
     * @var ?string
     */
    #[Column(name: 'issuer_id', type: 'varchar', length: 36)]
    protected ?string $issuerId = null;

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

    public function getScopeKey(): ?string
    {
        return $this->scopeKey;
    }

    public function getVoucherCode(): ?string
    {
        return $this->voucherCode;
    }

    /**
     * @return ?numeric-string
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getSecretDigest(): ?string
    {
        return $this->secretDigest;
    }

    public function getIssuedBy(): ?string
    {
        return $this->issuedBy;
    }

    public function getIssuerId(): ?string
    {
        return $this->issuerId;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setScopeKey(?string $scopeKey): self
    {
        $this->scopeKey = $scopeKey;
        return $this;
    }

    public function setVoucherCode(?string $voucherCode): self
    {
        $this->voucherCode = $voucherCode;
        return $this;
    }

    /**
     * @param ?numeric-string $amount
     */
    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function setSecretDigest(?string $secretDigest): self
    {
        $this->secretDigest = $secretDigest;
        return $this;
    }

    public function setIssuedBy(?string $issuedBy): self
    {
        $this->issuedBy = $issuedBy;
        return $this;
    }

    public function setIssuerId(?string $issuerId): self
    {
        $this->issuerId = $issuerId;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
